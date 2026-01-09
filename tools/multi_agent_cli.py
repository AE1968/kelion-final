"""
Multi-Agent CLI - Command Line Interface for the Kelion AI Multi-Agent System
Run development tasks with multiple AI agents working in parallel
"""

import asyncio
import sys
import os
import argparse
from pathlib import Path

# Add project root to path
sys.path.insert(0, str(Path(__file__).resolve().parent.parent))

from agents.orchestrator import Orchestrator, quick_task


def print_banner():
    """Print the welcome banner"""
    banner = """
╔══════════════════════════════════════════════════════════════════════════════╗
║                                                                              ║
║   🤖 KELION AI - Multi-Agent Development System                              ║
║                                                                              ║
║   ┌─────────────────────────────────────────────────────────────────────┐   ║
║   │                      🧠 ORCHESTRATOR                                 │   ║
║   └───────────────────────────┬─────────────────────────────────────────┘   ║
║                               │                                              ║
║       ┌───────────┬───────────┼───────────┬───────────┬───────────┐         ║
║       │           │           │           │           │           │         ║
║       ▼           ▼           ▼           ▼           ▼           ▼         ║
║   ┌───────┐   ┌───────┐   ┌───────┐   ┌───────┐   ┌───────┐   ┌───────┐    ║
║   │ 🔧    │   │ 🎨    │   │ 🧪    │   │ 📝    │   │ 🔒    │   │ 💾    │    ║
║   │BACKEND│   │FRONT  │   │TESTER │   │ DOCS  │   │SECUR. │   │  DB   │    ║
║   └───────┘   └───────┘   └───────┘   └───────┘   └───────┘   └───────┘    ║
║                                                                              ║
╚══════════════════════════════════════════════════════════════════════════════╝
"""
    print(banner)


def print_status(orchestrator: Orchestrator):
    """Print current status of all agents"""
    status = orchestrator.get_status()
    
    print("\n📊 Agent Status:")
    print("─" * 50)
    
    for role, info in status["agents"].items():
        emoji = {
            "backend": "🔧",
            "frontend": "🎨",
            "tester": "🧪",
            "documentation": "📝",
            "security": "🔒",
            "database": "💾"
        }.get(role, "🤖")
        
        status_emoji = {
            "idle": "⚪",
            "working": "🔵",
            "completed": "🟢",
            "error": "🔴"
        }.get(info["status"], "⚪")
        
        print(f"  {emoji} {info['name']:<20} {status_emoji} {info['status']}")
    
    if status["projects"]:
        print("\n📁 Active Projects:")
        print("─" * 50)
        for proj in status["projects"]:
            print(f"  • {proj['name']} ({proj['tasks_completed']}/{proj['tasks_total']} tasks)")


async def interactive_mode(orchestrator: Orchestrator):
    """Run in interactive mode"""
    print("\n💡 Interactive Mode - Type your development requests")
    print("   Commands: 'status', 'quit', 'help'")
    print("─" * 50)
    
    while True:
        try:
            request = input("\n🎯 Your request: ").strip()
            
            if not request:
                continue
            
            if request.lower() == 'quit':
                print("\n👋 Goodbye!")
                break
            
            if request.lower() == 'status':
                print_status(orchestrator)
                continue
            
            if request.lower() == 'help':
                print("""
Available Commands:
  status   - Show status of all agents
  quit     - Exit the program
  help     - Show this help message
  
Or type any development request, for example:
  "Add a login page with email and password"
  "Create an API endpoint for user registration"
  "Write unit tests for the authentication module"
                """)
                continue
            
            # Execute the request
            print(f"\n🚀 Processing: {request[:60]}...")
            
            project = await orchestrator.execute_project(
                name=f"Task-{len(orchestrator.active_projects) + 1}",
                user_request=request
            )
            
            # Print summary
            summary = orchestrator.get_project_summary(project)
            print(summary)
            
            # Show code changes
            changes = orchestrator.get_all_code_changes(project)
            if changes:
                print("\n📝 Code Changes Generated:")
                print("─" * 50)
                for change in changes:
                    action_emoji = {"create": "➕", "modify": "✏️", "delete": "❌"}.get(change.get("action", ""), "📄")
                    print(f"  {action_emoji} {change.get('file', 'unknown')}")
                    if change.get("content"):
                        preview = change["content"][:200] + "..." if len(change.get("content", "")) > 200 else change.get("content", "")
                        print(f"     Preview: {preview[:100]}")
            
        except KeyboardInterrupt:
            print("\n\n👋 Interrupted. Goodbye!")
            break
        except Exception as e:
            print(f"\n❌ Error: {e}")


async def main():
    parser = argparse.ArgumentParser(
        description="Kelion AI Multi-Agent Development System",
        formatter_class=argparse.RawDescriptionHelpFormatter,
        epilog="""
Examples:
  python multi_agent_cli.py                           # Interactive mode
  python multi_agent_cli.py "Add user authentication" # Execute single task
  python multi_agent_cli.py --status                  # Show agent status
        """
    )
    
    parser.add_argument(
        "request",
        nargs="?",
        help="Development request to process"
    )
    parser.add_argument(
        "--status",
        action="store_true",
        help="Show status of all agents"
    )
    parser.add_argument(
        "--api-key",
        help="Claude API key (or set CLAUDE_API_KEY env var)"
    )
    parser.add_argument(
        "--parallel",
        action="store_true",
        default=True,
        help="Execute tasks in parallel (default: True)"
    )
    parser.add_argument(
        "--sequential",
        action="store_true",
        help="Execute tasks sequentially"
    )
    
    args = parser.parse_args()
    
    print_banner()
    
    # Get API key
    api_key = args.api_key or os.getenv("CLAUDE_API_KEY") or os.getenv("ANTHROPIC_API_KEY")
    
    if not api_key:
        print("❌ Error: No API key provided.")
        print("   Set CLAUDE_API_KEY environment variable or use --api-key")
        return 1
    
    print(f"✅ API Key: ...{api_key[-8:]}")
    
    try:
        orchestrator = Orchestrator(api_key=api_key)
        print(f"✅ Initialized {len(orchestrator.agents)} specialized agents")
    except Exception as e:
        print(f"❌ Failed to initialize: {e}")
        return 1
    
    if args.status:
        print_status(orchestrator)
        return 0
    
    if args.request:
        # Single task mode
        print(f"\n🎯 Request: {args.request}")
        
        project = await orchestrator.execute_project(
            name="CLI Task",
            user_request=args.request,
            parallel=not args.sequential
        )
        
        summary = orchestrator.get_project_summary(project)
        print(summary)
        
        return 0
    
    # Interactive mode
    await interactive_mode(orchestrator)
    return 0


if __name__ == "__main__":
    exit_code = asyncio.run(main())
    sys.exit(exit_code)
