# Multi-Agent Orchestration System for Kelion AI
# Swarm/Crew AI Pattern Implementation
# Professional Multi-Provider Support for True Parallelism

from .orchestrator import Orchestrator
from .base_agent import BaseAgent, AgentRole, AgentStatus, Task, TaskResult
from .specialized_agents import (
    BackendAgent, FrontendAgent, TesterAgent, 
    DocumentationAgent, SecurityAgent, DatabaseAgent
)
from .providers import (
    ProviderPool, ProviderType, ProviderConfig,
    ClaudeProvider, OpenAIProvider, GoogleProvider,
    create_provider_pool_from_env
)

__all__ = [
    # Core
    'Orchestrator',
    'BaseAgent',
    'Task',
    'TaskResult',
    'AgentRole',
    'AgentStatus',
    
    # Specialized Agents
    'BackendAgent',
    'FrontendAgent',
    'TesterAgent',
    'DocumentationAgent',
    'SecurityAgent',
    'DatabaseAgent',
    
    # Multi-Provider System
    'ProviderPool',
    'ProviderType',
    'ProviderConfig',
    'ClaudeProvider',
    'OpenAIProvider', 
    'GoogleProvider',
    'create_provider_pool_from_env',
]

__version__ = "1.0.0"
