Magento Cloud Git Deployer
=====

Because deploying git to cloud is hard

The traditional [git-based cloud deployment process](https://wiki.corp.magento.com/x/KbbrBw) is long and messy. Git deployments do not get the same attention that composer deployments do. There is also the [VCS installer tool](https://github.com/magento-commerce/magento-vcs-installer) but the setup for that is also not straight-forward. 

Also, switching between a composer-based installation to a git-based (and back again) can be very challenging. Also switching between magento versions even within the same deployment mechanism can also be hard and full of undocumented bugs and required configuration.

That's where the Magento Cloud Git Deployer comes to the rescue.

# What does this tool do?

This tool allows you to consistently deploy your code to your cloud project using either the traditional git-based workflow and or via the VCS installer. It will also add any non-magento dependencies from your `composer.json` files found within `app/code/` modules. 

It also provides automated fixes for common issues as they arise day-to-day that are not documented as well as fixing undocumented required configuration for various tooling such as composer2 or different magento versions.

# But why?

With this tool, preparing for a deployment is always a single step. And aside from the issues presented in the intro description, when you want to switch branches on your instance you often have to start from scratch because the official tooling such as `ece-tools` and `vcs-installer` are buggy and often have undocumented needs. 

# Installation

1. Add the repo to global composer `composer global config repositories.deployer vcs git@github.com:nathanjosiah/magento-cloud-git-deployer.git`
1. Enable dev dependencies `composer global config minimum-stability dev`
1. Install the tool `composer global require nathanjosiah/magento-cloud-git-deployer`
1. Verify your installation with `cloud-deployer --version`. You should see something like `Magento Cloud Git Deployer CLI dev`.

# Update
1. Run `cloud-deployer self-update`

# Usage
Before either strategy is used below, ensure you have configured your `auth.json` file in the root of your project:

```json
{
    "github-oauth": {
        "github.com": "<token>"
    },  
    "http-basic": {
         "connect20-qa01.magedevteam.com": {
            "username": "<public key>",
             "password": "<private key>"
         },  
        "repo.magento.com": {
            "username": "<public key>",
            "password": "<private key>"
        },  
        "github.com": {
           "username": "<your username>",
           "password": "<your personal access token>"
        }  
    }   
}
```

## Traditional git deployment strategy
1. Ensure you have configured your `.magento.env.yaml` file with what you want to deploy. You may run `cloud-deployer project:init` to quickly get started.
2. Run `cloud-deployer environment:prepare` _from within your cloud folder containing your cloud project git repo_. You can optionally specify the directory you want to prepare via `cloud-deployer environment:prepare <path>`

   You can exclude additional directories via the `--exclude` flag. e.g. `cloud-deployer --exclude app2 --exclude special my-project/path`. Note that this currently can only exclude top-level directories so if you want to exclude `foo/bar/baz` you have to specific `--exclude foo`.

   If you have been instructed to apply a hotfix because of a current cloud issue, you may pass them as arguments such as `--hotfix monolog`. You can also specify multiple such as `--hotfix monolog --hotfix laminas`


## VCS installer strategy
1. 

## To prepare for a deployment to a different branch

1. Update your `.magento.env.yaml` and run `cloud-deployer environment:prepare`.
1. If you are switching between release-lines (e.g. 2.4.x -> 2.3.x) then you will probably have to remove the existing Magento data from your instance. 
   To do that, ssh into your cloud instance (via `magento-cloud ssh`) and run the following:
   ```
   rm -rf app/etc/* && rm -rf pub/media/* && rm -rf pub/static/* && rm -rf var/*
   mysql -u mysql -h database.internal -e 'DROP DATABASE main; CREATE DATABASE main;'
   redis-cli -p 6379 -h redis.internal FLUSHALL
   ```
   
   This is the fastest and cleanest method to fully remove the existing Magento installation. The `bin/magento setup:uninstall` command is slow and leaves a bunch of files behind. It also doesn't flush redis. 
1. Commit and push all the files.