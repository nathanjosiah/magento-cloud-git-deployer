Magento Cloud Git Deployer
=====

Because deploying git to cloud is hard

Even when it works, the [git-based deployment process](https://wiki.corp.magento.com/x/KbbrBw) is long and messy. Git deployments do not get the same attention that composer deployments do even though this is the primary workflow of all internal core development. Sure there is the [VCS installer tool](https://github.com/magento-commerce/magento-vcs-installer) but it is not maintained and is also complicated to use plus is just as inconsistent.

That's where the Magento Cloud Git Deployer comes to the rescue.

# What does this tool do?

This tool deletes everything (configurable) except for `auth.json`, `.git`, `.magento.env.yaml`, and `app/` and resets your cloud project with a fresh mainline cloud template that is configured for the official git-based workflow. It will also add any non-magento dependencies from your `composer.json` files found within `app/code/` modules. 

Then it will then run `composer update` as well as `ece-tools dev:git:update-composer` followed by additional configuration of `composer.json` from the official guide.

# But why?

With this tool, preparing for a deployment is always a single step. And aside from the issues presented in the intro description, when you want to switch branches on your instance you often have to start from scratch because `ece-tools` is buggy with this workflow. 

# Usage:

1. Clone the project to a local folder such as in `~/`. e.g. `git clone git@github.com:nathanjosiah/magento-cloud-git-deployer.git ~/`
1. Install the composer dependencies. e.g. (`composer install -d ~/magento-cloud-git-deployer`) 
1. Ensure you have configured your `.magento.env.yaml` file with what you want to deploy.
1. Run `php ~/magento-cloud-git-deployer/bin/deploy.php environment:prepare` _from within your cloud folder containing your cloud project git repo_. You can optionally specify the directory you want to prepare via `php ~/magento-cloud-git-deployer/bin/deploy.php environment:prepare <path>`
   
   You can exclude additional directories via the `--exclude` flag. e.g. `php ~/magento-cloud-git-deployer/bin/deploy.php --exclude app --exclude special my-project/path`. Note that this currently can only exclude top-level directories so if you want to exclude `foo/bar/baz` you have to specific `--exclude foo`.
   
   ⚠️🚨 By default the script will use your current working directory and irreversibly delete things. **Make sure you use the right directory**. 

## To prepare for a deployment to a different branch

1. Follow steps 3-5 again with the branches you want in your `.magento.env.yaml`.
1. If you are switching between release-lines (e.g. 2.4.x -> 2.3.x) then you will probably have to remove the existing Magento data from your instance. 
   To do that, ssh into your cloud instance and run the following:
   ```
   rm -rf app/etc/* && rm -rf pub/media/* && rm -rf pub/static/* && rm -rf var/*
   mysql -u mysql -h database.internal -e 'DROP DATABASE main; CREATE DATABASE main;'
   redis-cli -p 6379 -h redis.internal FLUSHALL
   ```
   
   This is the fastest and cleanest method to fully remove Magento. The `bin/magento setup:uninstall` command is slow and leaves a bunch of files behind. It also doesn't flush redis. 