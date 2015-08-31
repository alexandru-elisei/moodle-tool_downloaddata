## moodle-tool_downloaddata

#### Description
This is a plugin for Moodle to download site data (at the moment, courses and users) to a CSV or Excel 2007 file. There is also a cli script to use the functionality from the terminal.

#### Installation
This plugin has been tested to work with Moodle 2.6. There are no guarantess it will work with earlier versions.
General installation procedures are those common for all moodle plugins: https://docs.moodle.org/29/en/Installing_plugins
The basic process involves cloning the repository into MOODLE_ROOT_DIRECTORY/admin/tool/downloaddata:

    git clone https://github.com/alexandru-elisei/moodle-tool_downloaddata.git MOODLE_ROOT_DIRECTORY/admin/tool/downloaddata,

replacing MOODLE_ROOT_DIRECTORY with the actual moodle installation root directory path.

As an alternative, you can also download the zip file and extract it to the same location. If you are cloning the git repository, keep in mind that this also creates a .git directory.

#### Usage
The plugin create an entry in Site administration -> Reports -> Download data. From here you can access the full functionality of the plugin.

For the cli script, navigate to MOODLE_ROOT_DIRECTORY/admin/tool/downloaddata/cli and do the following:

    php downloaddata.php --data=users --format=xls --roles=all > output.xls

You can see a list of all the available options by doing:

    php uploadusercli.php --help

#### Copyright
Copyright (C) Alexandru Elisei 2015 and beyond, All right reserved.

moodle-tool_downloaddata is free software; you can redistribute it and/or modify it under the terms of the GNU Lesser General Public License as published by the Free Software Foundation; either version 3 of the license, or (at your option) any later version.

This software is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of the MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser General Public License for more details.

#### UPB specific functionality
In locallib.php (lines 342 to 348) it is possible to specify which template course to use based on the startdate of the course (you still need to use the "useoverwrites" cli flag to enable this functionality). Comment out the lines if you want to disable using specific course templates based on startdate.
