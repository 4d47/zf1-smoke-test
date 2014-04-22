# ZF Smoke Test

![](http://i.imgur.com/VqP98nA.jpg)

It happens all too often, it works on local, then the code is pushed and it fails
miserably.  Or worst, it looks like working but few hours later the client tries
out a major feature and find an explosion.

The reasons are various, a need-to-be writable directory, a required extension, etc.

This module simply test for those various stupid big cracks and either respond
`ok` or explode loudly (with a 500). So drop the module and point your browser
to `/smoke-test`.  Some of the tests can be configured in application.ini.

    smoke-test.phpversion = 5.4
    smoke-test.extensions[] = iconv
    smoke-test.extensions[] = gettext
    smoke-test.writables[] = APPLICATION_PATH "/../data/uploads"
    smoke-test.readables[] = APPLICATION_PATH "/../public/modules/admin/static/libs/foo.min.js"
    smoke-test.latests.vendor.out = APPLICATION_PATH "/../public/modules/admin/static/libs/vendor.min.js"
    smoke-test.latests.vendor.src = APPLICATION_PATH "/../public/modules/admin/static/libs/vendor"

The [code is here](https://github.com/4d47/zf1-smoke-test/blob/master/smoke-test/controllers/IndexController.php). The [elephant image](http://riosriosrios.wordpress.com/2008/08/09/elephant/) is from [riosriosrios](http://riosriosrios.wordpress.com). Got a crack ? [fork](https://github.com/4d47/zf1-smoke-test/fork) !

