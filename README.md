# Facebook Group to WordPress

A simple plugin that imports posts from facebook groups to your WordPress blog, every half hour!

> **Acknowledgement:**
> You shouldn't use this plugin to pull posts from the groups you didn't created. The author doesn't take any responsibility for any kind of abuse.

### What it does & doesn't

* Imports from facebook group and inserts as `fb_group_post` post type
* No chance for duplication
* It imports comments as well
* Runs every half hour via WordPress cron system
* Adds group id, author name and ID, post link as post meta
* If you want to trigger the importing manually, go to `http://example.com/?fb2wp_test`
* Import historical (paginated) posts. To do this, go to `http://example.com/?fb2wp_hist` and it'll automatically start the import process. Only admins can run this task.


## Contribute
If you want to contribute on this project, you are more than welcome.


## Author
[Tareq Hasan](http://tareq.wedevs.com)