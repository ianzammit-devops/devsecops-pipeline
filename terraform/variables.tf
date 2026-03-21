variable "aws_region" {
  description = "AWS region to deploy into"
  type        = string
  default     = "eu-west-1"
}

variable "image_tag" {
  description = "Docker image tag to deploy (commit SHA from GitHub Actions)"
  type        = string
}

variable "app_name" {
  description = "Application name used for naming resources"
  type        = string
  default     = "devsecops-pipeline"
}