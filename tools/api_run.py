"""
K1 API Server Entry Point - INTEGRAL VERSION
Railway Compatible - Production Ready

This script starts the K1 API server with Railway-compatible configuration.
"""

import os
import sys
from pathlib import Path

# Add parent directory to path for imports
sys.path.insert(0, str(Path(__file__).parent.parent))

from api.server import run


def main():
    """
    Main entry point for K1 API server
    
    Environment Variables:
        PORT: Server port (default: 8080, Railway sets this automatically)
        K1_API_TOKEN: API authentication token (required)
    """
    # Railway provides PORT env var
    port = int(os.getenv("PORT", 8080))
    
    # Bind to 0.0.0.0 for Railway (accessible from outside container)
    host = "0.0.0.0"
    
    # Verify API token is set
    api_token = os.getenv("K1_API_TOKEN")
    if not api_token or api_token == "dev-token":
        print("⚠️  WARNING: K1_API_TOKEN not set or using default dev-token!")
        print("   Set K1_API_TOKEN environment variable for production.")
    
    print("=" * 60)
    print("🚀 K1 API Server - INTEGRAL VERSION (Day 23+)")
    print("=" * 60)
    print(f"Host: {host}")
    print(f"Port: {port}")
    print(f"API Token: {'✅ SET' if api_token and api_token != 'dev-token' else '⚠️  DEFAULT'}")
    print("=" * 60)
    
    # Start server
    run(host=host, port=port)


if __name__ == "__main__":
    main()
