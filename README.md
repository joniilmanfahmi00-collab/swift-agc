# Swift AGC & Static Site Generator v1.0.0 (FREE With License!)

![Python](https://img.shields.io/badge/Python-3776AB?style=for-the-badge&logo=python&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white)
![Alpine.js](https://img.shields.io/badge/Alpine.js-8BC0D0?style=for-the-badge&logo=alpinejs&logoColor=white)
![Tailwind CSS](https://img.shields.io/badge/Tailwind_CSS-38B2AC?style=for-the-badge&logo=tailwind-css&logoColor=white)
![JSON](https://img.shields.io/badge/JSON-01984D?style=for-the-badge&logo=json&logoColor=white)
![Docker](https://img.shields.io/badge/Docker-2496ED?style=for-the-badge&logo=docker&logoColor=white)
![SQLite](https://img.shields.io/badge/SQLite-07405E?style=for-the-badge&logo=sqlite&logoColor=white)
![Nginx](https://img.shields.io/badge/Nginx-009639?style=for-the-badge&logo=nginx&logoColor=white)

## Overview
Swift AGC is a powerful tool designed to automate the generation of high-quality articles based on specific keywords. It leverages advanced AI technologies to create content that is not only informative but also engaging and well-structured. The generated articles are formatted in PHP, making it easy to publish them on various platforms.

## Purpose
The primary purpose of Swift AGC is to help content creators, marketers, and website owners generate relevant and SEO-friendly articles quickly. By using AI, it can produce content that is tailored to the target audience, ensuring higher engagement and better search engine rankings.

## Features
- **AI Automated Article Generation**: Swift AGC uses AI to generate articles based on the provided keywords, ensuring that the content is relevant and informative.
- **Easy Deploy with Docker**: Swift AGC can be easily deployed using Docker, allowing for a seamless setup and integration into existing workflows.
- **Multi AI Agent**: The tool incorporates multiple AI agents to enhance the quality and diversity of the generated content.
- **Multi Threads Generate**: Swift AGC can generate multiple articles simultaneously using multi-threading, significantly reducing the time required to produce content.
- **Generated Static Site**: Swift AGC can create a static site with the generated articles, making it easy to publish and manage content without the need for a dynamic backend.
- **Lightweight Databases (SQLite3)**: Swift AGC uses SQLite3 for lightweight and efficient data storage, ensuring quick access and management of generated content.
- **Containerized by Docker**: Swift AGC is fully containerized using Docker, allowing for easy deployment and scalability across different environments.

## Tech Stack
- **Python Backend**: The core programming language used for developing Swift AGC, enabling the implementation of AI algorithms and content generation logic.
- **SQLite3**: A lightweight database used for storing generated articles and configuration settings, ensuring efficient data management.
- **PHP Frontend**: The frontend of Swift AGC is built using PHP, providing a user-friendly interface for managing and publishing generated articles.
- **Alpine.js**: Alpine.js is used for enhancing the interactivity of the frontend, allowing for dynamic content updates and a smoother user experience. 
- **Tailwind CSS**: Tailwind CSS is utilized for styling the frontend, offering a modern and responsive design that is easy to customize.
- **JSON Configuration**: Swift AGC uses JSON files for configuration, allowing users to easily customize settings such as API keys, database paths, and output directories.
- **Docker**: Docker is used to containerize the application, ensuring that it can be easily deployed and run in any environment without compatibility issues.
- **Nginx**: Nginx is used as the web server to serve the generated static site efficiently.

## Requirements
- Docker: Ensure that Docker is installed and running on your system to deploy Swift AGC.
- API Keys: Obtain API keys for the AI services used by Swift AGC (Groq, Pexels, Gemini, Qwen) and update the `config.json` file accordingly.
- Configuration: Customize the `config.json` file to set up database paths, output directories, and other settings as needed.
- System Resources: Ensure that your system has sufficient resources (CPU, RAM) to run the AI models and generate content efficiently.

## Installation & Run Guide

1. **Clone the repository**:
   ```bash
   git clone <https://github.com/your-username/swift.git>
   cd swift
   ```
2. **Install Python Dependencies**:
   Ensure you have Python 3.9+ installed, then run:
   ```bash
   pip install requests google-generativeai groq duckduckgo-search colorama
   ```
3. **Configuration**:
   Open `engine/config.json` and fill in your API keys for Groq, Gemini, or Qwen, as well as Pexels if you plan to use it.
4. **Prepare Keywords**:
   Create a `keyword.txt` file inside the `engine/` directory. List your target keywords, one per line.
5. **Generate Articles**:
   Navigate to the engine directory and run the generator. Example using Gemini and DuckDuckGo:
   ```bash
   cd engine
   python swift.py --duckduckgo --gemini_agents --threads 3
   ```
6. **Launch the Web Interface**:
   Go back to the root directory and start the Docker containers:
   ```bash
   docker-compose up -d
   ```
   The site will be available at `http://localhost:8080`.

   docker-compose up -d
   ```
   The site will be available at `http://localhost:8080`.

## Deployment Guide (Production/VPS)

To deploy Swift AGC to a live server, follow these best practices:

1. **Server Setup**:
   - Use a Linux VPS (Ubuntu 22.04 LTS recommended).
   - Install Docker and Docker Compose.

2. **Deploying Services**:
   Run the containers in detached mode with the build flag to ensure all local changes are captured:
   ```bash
   docker-compose up -d --build
   ```

3. **Reverse Proxy & SSL**:
   For production, it is recommended to use **Nginx Proxy Manager** or a manual Nginx setup with **Certbot** to handle HTTPS. Point your domain/subdomain to the server's IP on port `8080`.

4. **Running the Engine in Background**:
   Since the Python script can take a long time to finish, use `tmux` or `screen` to keep the process running after you close your SSH session:
   ```bash
   tmux new -s swift_engine
   cd engine
   python3 swift.py --duckduckgo --gemini_agents --threads 5
   ```
   *Press `Ctrl+B` then `D` to detach from the session.*

## Contributing
Swift AGC is an open-source project. Contributions are welcome! If you have any suggestions, improvements, or bug fixes, please feel free to submit a pull request or open an issue on the GitHub repository.

## Developer Important Note!!!
This project is intended for personal use only. It is not allowed to resell or distribute the software without proper authorization. If you wish to use this project for commercial purposes, please contact the developer for licensing options.

## License
This project is licensed under the MIT License. See the [LICENSE](LICENSE) file for more details.
