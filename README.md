Prototype for automated acceptance testing of different enviorments using Docker.

I built this prototype during one of my internships to automatically set up enviroments of Wordpress and Magento2 projects with docker. The user has the option to choose which branch they wish to use.

Includes an overview of running instances, with the ability to stop one once it is no longer needed.

The application is written in PHP and only usses [guzzle](https://docs.guzzlephp.org/en/stable/) as a HTTP client. The application itself is also dockerized.

The application uses the github API to fetch the repositories and branches. This application also uses the Docker API to: 
- Build images from github projects;
- Manage images in the designated registry;
- Create, start and stop containers;
- Execute commands within running containers;