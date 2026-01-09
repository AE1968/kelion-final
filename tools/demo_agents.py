"""
Demo script pentru Multi-Agent System
Demonstrează cum funcționează sistemul cu un exemplu simplu
"""

import asyncio
import os
import sys
from pathlib import Path

# Add project root to path
sys.path.insert(0, str(Path(__file__).resolve().parent.parent))


async def demo():
    """
    Demonstrează sistemul multi-agent cu un task simplu.
    """
    print("=" * 70)
    print("🤖 KELION AI - Multi-Agent System Demo")
    print("=" * 70)
    
    # Import after path setup
    from agents.orchestrator import Orchestrator
    
    # Get API key
    api_key = os.getenv("CLAUDE_API_KEY") or os.getenv("ANTHROPIC_API_KEY")
    
    if not api_key:
        # Try to read from SETUP_INFO.md
        setup_file = Path(__file__).resolve().parent.parent.parent / "SETUP_INFO.md"
        if setup_file.exists():
            content = setup_file.read_text(encoding='utf-8')
            # Simple extraction
            if "Claude API Key:" in content:
                import re
                match = re.search(r'Claude API Key:\s*`([^`]+)`', content)
                if match:
                    api_key = match.group(1)
                    print(f"✅ Loaded API key from SETUP_INFO.md")
    
    if not api_key:
        print("❌ No API key found!")
        print("   Please set CLAUDE_API_KEY environment variable")
        return
    
    print(f"✅ API Key: ...{api_key[-8:]}")
    
    # Initialize Orchestrator
    print("\n📦 Initializing Multi-Agent System...")
    orchestrator = Orchestrator(api_key=api_key)
    
    print(f"✅ Loaded {len(orchestrator.agents)} specialized agents:")
    for role, agent in orchestrator.agents.items():
        print(f"   • {agent.name} ({role.value})")
    
    # Demo task
    demo_request = """
    Create a simple health check endpoint for the API that:
    1. Returns the server status
    2. Shows uptime
    3. Lists active services
    """
    
    print("\n" + "=" * 70)
    print("📋 Demo Task:")
    print("=" * 70)
    print(demo_request)
    
    print("\n🚀 Starting execution...")
    print("   (This will call Claude API to generate solutions)")
    
    try:
        project = await orchestrator.execute_project(
            name="Demo Health Check",
            user_request=demo_request
        )
        
        print("\n" + "=" * 70)
        print("📊 Results:")
        print("=" * 70)
        
        summary = orchestrator.get_project_summary(project)
        print(summary)
        
        # Show generated code
        changes = orchestrator.get_all_code_changes(project)
        
        if changes:
            print("\n" + "=" * 70)
            print("💻 Generated Code:")
            print("=" * 70)
            
            for i, change in enumerate(changes, 1):
                print(f"\n--- File {i}: {change.get('file', 'unknown')} ---")
                print(f"Action: {change.get('action', 'unknown')}")
                print("\nContent:")
                print(change.get('content', 'No content')[:1000])
                if len(change.get('content', '')) > 1000:
                    print("\n... (truncated)")
        
        print("\n" + "=" * 70)
        print("✅ Demo completed successfully!")
        print("=" * 70)
        
    except Exception as e:
        print(f"\n❌ Error during demo: {e}")
        import traceback
        traceback.print_exc()


if __name__ == "__main__":
    asyncio.run(demo())
