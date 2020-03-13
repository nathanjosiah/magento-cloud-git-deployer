When the cloud just don't work no moe.
=====

This will delete everything except for `auth.json`, `.git`, `.magento.env.yaml`, and `app/` and reset your cloud project with a fresh mainline copy that is configured for the git-based workflow and also add any non-magento dependencies from your `composer.json` files found within `app/code/` modules. 

### Usage:

Run `php nuke-cloud.php` _from within your local folder containing your cloud environment git repo to be nuked_

You can exclude additional directories via the `--exclude` flag. e.g. `php nuke-cloud.php --exclude app --exclude special my-project/path`. Note that this currently can only exclude top-level directories so if you want to exclude `foo/bar/baz` you have to specific `--exclude foo`.

⚠️🚨 By default the script will use your current working directory and irreversibly delete things. **Make sure you use the right directory**. 

You may optionally provide a directory to run in: `php nuke-cloud.php my-path/to/mage-cloud`

Once the command has finished and you have run `composer update`, make sure your `.magento.env.yaml` configuration is what you want to deploy before you run `dev:git:update-composer`.