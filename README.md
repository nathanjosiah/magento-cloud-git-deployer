Magento Cloud Git Deployer
=====

Git deployments do not get the same attention that composer deployments do.

Also, switching between a composer-based installation to a git-based (and back again) can be very challenging. Also switching between magento versions even within the same deployment mechanism can also be hard and full of undocumented bugs and required configuration.

That's where the Magento Cloud Git Deployer comes to the rescue.

# What does this tool do?

This tool allows you to consistently deploy your code to your cloud project using either the traditional git-based workflow and or via the VCS installer. It will also add any non-magento dependencies from your `composer.json` files found within `app/code/` modules. 

It also provides automated fixes for common issues as they arise day-to-day that are not documented as well as fixing undocumented required configuration for various tooling such as composer2 or different magento versions.

# Getting Started

## Installation
1. Add the repo to global composer `composer global config repositories.deployer vcs git@github.com:nathanjosiah/magento-cloud-git-deployer.git`
2. Enable dev dependencies `composer global config minimum-stability dev`
3. Install the tool `composer global require nathanjosiah/magento-cloud-git-deployer`
4. Verify your installation with `cloud-deployer --version`. You should see something like `Magento Cloud Git Deployer CLI dev`.

## Initial project setup
1. [Install the cloud CLI](https://devdocs.magento.com/cloud/reference/cli-ref-topic.html)
2. Clone your cloud project with git (from the [magento.cloud](https://magento.cloud/) UI) or magento-cloud (`magento-cloud project:get ...`).
4. Open your project in a shell/terminal and run `cloud-deployer project:init`.
5. Configure setup your `auth.json` credentials.
7. Run `cloud-deployer environment:prepare` using the arguments you need to deploy your code. Review the `VCS installer strategy` section below for more details.  
8. Commit all files.
9. If you are pushing to an existing environment, you may need to follow the [steps below](#to-prepare-for-a-deployment-to-a-different-branch) before pushing your code.
10. Push your code.

## Subsequent deployments
1. Run `cloud-deployer environment:prepare` with the arguments needed for your deployment.
2. Commit and push. If you are changing versions of magento or branches you may need to follow the [steps below](#to-prepare-for-a-deployment-to-a-different-branch) before pushing your code.


# Update
1. Run `cloud-deployer self-update`

# Usage
Before you follow the steps below, ensure you have configured your `auth.json` file in the root of your project. A stub will be created for you when you run `cloud-deployer project:init <type>`

## VCS installer strategy
 Ensure you have configured your `.magento.env.yaml` file with what you want to deploy. You may run `cloud-deployer project:init` to quickly get started.
 Run `cloud-deployer environment:prepare` _from within your cloud folder containing your cloud project git repo_. You can optionally specify the directory you want to prepare via `cloud-deployer environment:prepare <path>`
   This command has many different parameters available to control what is being deployed.

   Option|Description|Default
   ------|-----------|-------
   `--ce`|Which CE version you want to use. The format is `<organization>/<ref>`. For example, to use branch `AC-123` in repo `magento-cia/magento2ce` you would specify `--ce magento-cia/dev-AC-123`.| `magento-commerce/dev-2.4-develop`.
   `--ee`|Same as `--ce` except for EE.<br/><br/>You may also omit the argument value to skip installing EE. For example `env:prep --ce magento-cia/dev-AC-123 --ee --b2b --fastly --sp` will use a custom branch for CE and not install anything else.| `magento-commerce/dev-2.4-develop` 
   `--b2b`|Same as `--ee` except for B2B.|`magento-commerce/dev-develop` 
   `--sp`|Same as `--ee` except for security-package.|`magento-commerce/dev-develop` 
   `--fastly`|Essentially the same as `--ee` except for fastly. The format of this argument is slightly different because this isn't an official magento extension. See `--add` for more details.|`fastly/fastly-magento2:dev-master` 
   `--add` \[Supports multiple]|Allows you to add any additional extension. The format of this argument is slightly different than `--ce` or `--ee`. The format of this option is `<organization>/<repo>:<branch>`. For example, you can install ASI with `--add magento-commerce/adobe-stock-integration:dev-develop`.<br/><br/>Please notes that this can be used multiple times to add multiple repos. For example, `--add something --add something-else`.|None 


## To prepare for a deployment to a different branch

1. Run `cloud-deployer project:init` and enter the target release (without any `-p*` identifiers. For example, if targeting 2.3.7-p3 you would enter `2.3.7`)
2. Run `cloud-deployer environment:prepare <params>`.
3. If you are switching between release-lines (e.g. 2.4.x -> 2.3.x) then you will probably have to remove the existing Magento data from your instance. 
   To do that, ssh into your cloud instance (via `magento-cloud ssh`) and run the following:
   ```
   rm -rf app/etc/* && rm -rf pub/media/* && rm -rf pub/static/* && rm -rf var/*
   mysql -u mysql -h database.internal -e 'DROP DATABASE main; CREATE DATABASE main;'
   redis-cli -p 6379 -h redis.internal FLUSHALL
   ```
   
   This is the fastest and cleanest method to fully remove the existing Magento installation. The `bin/magento setup:uninstall` command is slow and leaves a bunch of files behind. It also doesn't flush redis. 
4. Commit and push all the files.