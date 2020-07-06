moodle-assignsubmission_maharaws
============================

Mahara assignment submission plugin for Moodle 3.5+
- https://github.com/MaharaProject/moodle-assignsubmission_maharaws

This plugin adds Mahara page submission functionality to assignments in
Moodle. It requires configuration of Mahara Web Services for REST and OAuth 1.x.

This plugin allows a teacher to add a "Mahara" item to the submission
options for a Moodle assignment. Students can then select one of the pages
or collections from their Mahara portfolio as part of their assignment
submission.

The submitted Mahara page or collection can be locked from editing in
Mahara if you wish, the same as if it had been submitted to a Mahara group.
Depending on settings, it may be left permanently locked, or get unlocked
when submission has been graded (or if grading workflow is used, its state
changed to "Released").


Installation
------------
1. Make sure that your Moodle and Mahara versions are up to date.

2. If you are using Mahara 20.10 or earlier, apply the patches listed below

3. Copy the contents of this project to mod/assign/submission/maharaws in your Moodle site.

4. Proceed with plugin installation in Moodle.


Mahara Configuration
--------------------

1. Enable webservices:

      1.1. Navigate to 'Site Administration Menu (🔧)-> Web service configuration'.

      1.2. Enable 'Accept incoming web service requests', REST, OAuth.

2. Generate a consumer key and consumer secret:

      2.1. Navigate to 'Site Administration Menu (🔧) ->  External Apps'.

      2.2. Type a name for the application into the application text input e.g. Moodle.

      2.3. Select Moodle Assignment Submission from the drop down list.

      2.4. Click 'Add'. You will then see the consumer key and secret. These will be used when configuring the Assignment activity in Moodle.

3. Enable user creation (optional)

      3.1. Navigate to 'Site Administration Menu (🔧) ->  External Apps'.

      3.2. Click the cog next to the newly created external application.

      3.3. Enable 'Auto-create accounts' and click save.


Creating a new Mahara assignment in Moodle
------------------------------------------

1. Add an assignment in Moodle.

2. Select Mahara as the assignment submission type.

3. Fill in the following:
      - URL of your Mahara site.
      - Mahara Web Services OAuth Key (from the external apps list in Mahara).
      - Mahara Web Services Oauth secret.




A little info about what it does:
---------------------------------

This plugin adds a "Mahara" submission method to "assignment" activities in Moodle.
When a teacher creates an "assignment" activity, they'll see "Mahara" as one of the
submission method options. This submission method will ask students to select one
of their pages or collections from Mahara, to include as part of their assignment
submission. (Therefore, this plugin requires your Moodle site to be connected to a
Mahara site via Mahara Web Services.)

* Individual pages that are part of collections cannot be picked on their own (the entire collection must be picked instead).
* Pages and collections that are already locked due to being submitted to a Mahara group or another Moodle assignment, are also not available.

Optionally, the assignment may lock the submitted pages and collections
from being edited in Mahara. This is recommended, because otherwise
students will be able to continue editing part of their assignment
submission even after the assignment deadline. The teacher may choose whether
submitted pages and collection will be unlocked after grading, or when the
grading workflow state changes to "Released".

If you choose to use locking, note that:
* Pages & collections that are part of a draft submission will be not be locked until the draft is submitted.
* The Mahara page will be locked if the submission is submitted OR the submission is "locked" via the Moodle gradebook.
* If a submission is "reopened" via the Moodle gradebook, the page will become unlocked.

If the locking setting permits unlock after grading:
* If grading workflow is disabled, then grading the work will result in page unlocking.
* If grading workflow is enabled, then page unlocking will happen at the point when workflow state changes to "Released", whether the submission has been graded or not prior to that.

If you need help, try the Moodle-Mahara Integration forum on mahara.org: https://mahara.org/interaction/forum/view.php?id=30

Bugs and Improvements?
----------------------

If you've found a bug or if you've made an improvement to this plugin and want to share your code, please
open an issue in our Github project:
* https://github.com/MaharaProject/moodle-assignsubmission_maharaws/issues

Credits
-------

The original Moodle 1.9 version of this plugin was funded through a grant from
the [New Hampshire Department of Education](http://education.nh.gov/) to a collaborative group of the
following New Hampshire school districts:

 - Exeter Region Cooperative
 - Windham
 - Oyster River
 - Farmington
 - Newmarket
 - Timberlane School District

The upgrade to Moodle 2.0 and 2.1 was written by Aaron Wells at [Catalyst](https://catalyst.net.nz) and
supported by [NetSpot](http://netspot.com.au/) and [Pukunui Technology](http://pukunui.com/).

The upgrade to the Moodle 2.3 mod/assign module was developed by:

 - Philip Cali and Tony Box (box@up.edu) at [University of Portland](http://up.edu)
 - Ruslan Kabalin at [Lancaster University](http://lancaster.ac.uk/)

Subsequent updates to the plugin for Moodle 2.6 and 2.7 were implemented by
Aaron Wells at Catalyst IT with funding from:

 - [University of Brighton](http://brighton.ac.uk)
 - [Canberra University](http://canberra.edu.au)

The upgrade to use events for unlocking behaviour and supporting grading workflow
was developed by Lancaster University.

License
-------

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, version 3 or later of the License.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
