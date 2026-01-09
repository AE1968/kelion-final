"""
AI Providers - Support pentru multiple API-uri AI
Permite agenților să folosească diferite providere în paralel
"""

from __future__ import annotations
import os
import json
import httpx
from abc import ABC, abstractmethod
from dataclasses import dataclass
from typing import Any, Optional
from enum import Enum


class ProviderType(Enum):
    """Tipuri de provideri AI disponibili"""
    CLAUDE = "claude"
    OPENAI = "openai"
    GOOGLE = "google"
    LOCAL = "local"  # Pentru modele locale (Ollama, etc.)


@dataclass
class ProviderConfig:
    """Configurare pentru un provider AI"""
    provider_type: ProviderType
    api_key: str
    model: str
    base_url: Optional[str] = None
    max_tokens: int = 4096
    temperature: float = 0.7


class AIProvider(ABC):
    """Clasă abstractă pentru provideri AI"""
    
    def __init__(self, config: ProviderConfig):
        self.config = config
    
    @abstractmethod
    async def generate(self, system_prompt: str, messages: list[dict]) -> str:
        """Generează un răspuns de la AI"""
        pass
    
    @property
    def name(self) -> str:
        return self.config.provider_type.value


class ClaudeProvider(AIProvider):
    """Provider pentru Claude (Anthropic)"""
    
    async def generate(self, system_prompt: str, messages: list[dict]) -> str:
        async with httpx.AsyncClient(timeout=120.0) as client:
            response = await client.post(
                self.config.base_url or "https://api.anthropic.com/v1/messages",
                headers={
                    "x-api-key": self.config.api_key,
                    "anthropic-version": "2023-06-01",
                    "content-type": "application/json"
                },
                json={
                    "model": self.config.model,
                    "max_tokens": self.config.max_tokens,
                    "system": system_prompt,
                    "messages": messages
                }
            )
            
            if response.status_code == 429:
                raise RateLimitError("Claude rate limit exceeded")
            if response.status_code != 200:
                raise ProviderError(f"Claude API Error: {response.status_code}")
            
            data = response.json()
            return data["content"][0]["text"]


class OpenAIProvider(AIProvider):
    """Provider pentru OpenAI (GPT-4, etc.)"""
    
    async def generate(self, system_prompt: str, messages: list[dict]) -> str:
        # Convertim formatul mesajelor pentru OpenAI
        openai_messages = [{"role": "system", "content": system_prompt}]
        openai_messages.extend(messages)
        
        async with httpx.AsyncClient(timeout=120.0) as client:
            response = await client.post(
                self.config.base_url or "https://api.openai.com/v1/chat/completions",
                headers={
                    "Authorization": f"Bearer {self.config.api_key}",
                    "Content-Type": "application/json"
                },
                json={
                    "model": self.config.model,
                    "max_tokens": self.config.max_tokens,
                    "temperature": self.config.temperature,
                    "messages": openai_messages
                }
            )
            
            if response.status_code == 429:
                raise RateLimitError("OpenAI rate limit exceeded")
            if response.status_code != 200:
                raise ProviderError(f"OpenAI API Error: {response.status_code}")
            
            data = response.json()
            return data["choices"][0]["message"]["content"]


class GoogleProvider(AIProvider):
    """Provider pentru Google (Gemini)"""
    
    async def generate(self, system_prompt: str, messages: list[dict]) -> str:
        # Convertim formatul pentru Gemini
        contents = []
        
        # System prompt ca primul mesaj
        contents.append({
            "role": "user",
            "parts": [{"text": f"System: {system_prompt}"}]
        })
        contents.append({
            "role": "model", 
            "parts": [{"text": "Understood. I will follow these instructions."}]
        })
        
        # Adăugăm mesajele
        for msg in messages:
            role = "user" if msg["role"] == "user" else "model"
            contents.append({
                "role": role,
                "parts": [{"text": msg["content"]}]
            })
        
        url = f"https://generativelanguage.googleapis.com/v1beta/models/{self.config.model}:generateContent?key={self.config.api_key}"
        
        async with httpx.AsyncClient(timeout=120.0) as client:
            response = await client.post(
                url,
                headers={"Content-Type": "application/json"},
                json={
                    "contents": contents,
                    "generationConfig": {
                        "maxOutputTokens": self.config.max_tokens,
                        "temperature": self.config.temperature
                    }
                }
            )
            
            if response.status_code == 429:
                raise RateLimitError("Google rate limit exceeded")
            if response.status_code != 200:
                raise ProviderError(f"Google API Error: {response.status_code}")
            
            data = response.json()
            return data["candidates"][0]["content"]["parts"][0]["text"]


class ProviderError(Exception):
    """Eroare generală de la provider"""
    pass


class RateLimitError(ProviderError):
    """Rate limit atins"""
    pass


class ProviderPool:
    """
    Pool de provideri AI pentru load balancing și fallback.
    Distribuie cererile între provideri pentru performanță maximă.
    """
    
    def __init__(self):
        self.providers: list[AIProvider] = []
        self._current_index = 0
    
    def add_provider(self, provider: AIProvider) -> None:
        """Adaugă un provider în pool"""
        self.providers.append(provider)
    
    def add_claude(self, api_key: str, model: str = "claude-sonnet-4-20250514") -> None:
        """Adaugă un provider Claude"""
        config = ProviderConfig(
            provider_type=ProviderType.CLAUDE,
            api_key=api_key,
            model=model
        )
        self.providers.append(ClaudeProvider(config))
    
    def add_openai(self, api_key: str, model: str = "gpt-4o") -> None:
        """Adaugă un provider OpenAI"""
        config = ProviderConfig(
            provider_type=ProviderType.OPENAI,
            api_key=api_key,
            model=model
        )
        self.providers.append(OpenAIProvider(config))
    
    def add_google(self, api_key: str, model: str = "gemini-1.5-pro") -> None:
        """Adaugă un provider Google"""
        config = ProviderConfig(
            provider_type=ProviderType.GOOGLE,
            api_key=api_key,
            model=model
        )
        self.providers.append(GoogleProvider(config))
    
    def get_next_provider(self) -> AIProvider:
        """Round-robin selection - distribuie uniform cererile"""
        if not self.providers:
            raise ValueError("No providers available in pool")
        
        provider = self.providers[self._current_index]
        self._current_index = (self._current_index + 1) % len(self.providers)
        return provider
    
    async def generate_with_fallback(
        self, 
        system_prompt: str, 
        messages: list[dict]
    ) -> tuple[str, str]:
        """
        Încearcă să genereze folosind providerii disponibili.
        Dacă unul eșuează, încearcă următorul.
        
        Returns:
            Tuple (răspuns, nume_provider)
        """
        errors = []
        
        # Încercăm fiecare provider
        for i in range(len(self.providers)):
            provider = self.get_next_provider()
            try:
                result = await provider.generate(system_prompt, messages)
                return result, provider.name
            except RateLimitError as e:
                errors.append(f"{provider.name}: Rate limited")
                continue
            except ProviderError as e:
                errors.append(f"{provider.name}: {e}")
                continue
            except Exception as e:
                errors.append(f"{provider.name}: {e}")
                continue
        
        raise ProviderError(f"All providers failed: {'; '.join(errors)}")
    
    def __len__(self) -> int:
        return len(self.providers)
    
    @property
    def provider_names(self) -> list[str]:
        return [p.name for p in self.providers]


def create_provider_pool_from_env() -> ProviderPool:
    """
    Creează un pool de provideri din variabilele de mediu.
    
    Environment variables:
        CLAUDE_API_KEY / ANTHROPIC_API_KEY
        OPENAI_API_KEY
        GOOGLE_API_KEY / GEMINI_API_KEY
    """
    pool = ProviderPool()
    
    # Claude
    claude_key = os.getenv("CLAUDE_API_KEY") or os.getenv("ANTHROPIC_API_KEY")
    if claude_key:
        pool.add_claude(claude_key)
    
    # OpenAI
    openai_key = os.getenv("OPENAI_API_KEY")
    if openai_key:
        pool.add_openai(openai_key)
    
    # Google
    google_key = os.getenv("GOOGLE_API_KEY") or os.getenv("GEMINI_API_KEY")
    if google_key:
        pool.add_google(google_key)
    
    return pool
