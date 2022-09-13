# The name of the repository, appears before the image name
REPO = localhost

# Name of the actual image
NAME = docker-deployer

# The docker registry where we want to store the image
REGISTRY = localhost:5000


ifdef VERSION
 	IMAGE_NAME = $(REPO)/$(NAME):$(VERSION)
else
	IMAGE_NAME = $(REPO)/$(NAME)
endif

.PHONY: build run push tag_latest

build:
	@docker build -t $(IMAGE_NAME) .

run:
	@if docker ps | awk '{ print $$2 }' | grep $(NAME); then echo "$(IMAGE_NAME) is already running."; false; fi
	docker run -e GITHUB_API_KEY=$(GITHUB_API_KEY) -e GITHUB_USERNAME=$(GITHUB_USERNAME) --name $(NAME) -d -p80:80 --restart always $(IMAGE_NAME)

push:
	@docker tag $(IMAGE_NAME) $(REGISTRY)/$(IMAGE_NAME)
	docker push $(REGISTRY)/$(IMAGE_NAME)

