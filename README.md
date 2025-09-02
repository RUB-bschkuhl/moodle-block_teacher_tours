# Example Block #

A basic Moodle block plugin template with essential functionality.

This plugin provides a foundation for creating custom Moodle blocks with proper capabilities, language support, and database integration.

## Installing via uploaded ZIP file ##

1. Log in to your Moodle site as an admin and go to _Site administration >
   Plugins > Install plugins_.
2. Upload the ZIP file with the plugin code. You should only be prompted to add
   extra details if your plugin type is not automatically detected.
3. Check the plugin validation report and finish the installation.

## Installing manually ##

The plugin can be also installed by putting the contents of this directory to

    {your/moodle/dirroot}/blocks/example

Afterwards, log in to your Moodle site as an admin and go to _Site administration >
Notifications_ to complete the installation.

Alternatively, you can run

    $ php admin/cli/upgrade.php

to complete the installation from the command line.

## Features ##

- Basic block display with customizable content
- Proper capability management (view, add instance)
- Multi-language support (English/German)
- Database table example for storing block data
- Follows Moodle coding standards and security practices

## Customization ##

1. **Rename the plugin**: Replace "example" with your desired name throughout all files
2. **Update content**: Modify the `get_content()` method in `block_teacher_tours.php`
3. **Add capabilities**: Extend `db/access.php` with additional permissions
4. **Database**: Modify `db/install.xml` if you need custom tables
5. **Styling**: Add CSS in a `styles.css` file

## License ##

2025 Your Name <your.email@example.com>

This program is free software: you can redistribute it and/or modify it under
the terms of the GNU General Public License as published by the Free Software
Foundation, either version 3 of the License, or (at your option) any later
version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY
WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
PARTICULAR PURPOSE.  See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with
this program.  If not, see <https://www.gnu.org/licenses/>.
