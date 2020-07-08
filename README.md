moodle-assignsubmission_maharaws
============================

Mahara assignment submission plugin for Moodle 3.5+
https://github.com/catalyst/assignsubmission-maharaws

This plugin adds Mahara portfolio submission functionality to assignments in
Moodle. It requires configuration of Mahara web services for REST and OAuth 1.x.

This plugin allows a teacher to add a 'Mahara' item to the submission
options in a Moodle assignment. Students can then select one of the pages
or collections from their personal Mahara account as part of their assignment
submission.

The submitted Mahara page or collection can be locked from editing in
Mahara if you wish, the same as if it had been submitted to a Mahara group.
Depending on settings, it may be left permanently locked, or get unlocked
when the submission has been graded (or if the grading workflow is used, its state
changed to "Released").


Install the Moodle plugin (and Mahara patches)
--------------------------------------------------

1. Make sure that your Moodle and Mahara versions are up to date.

2. If you are using Mahara 20.04 or earlier, apply the patches from the [issue report](https://bugs.launchpad.net/mahara/+bug/1882461).

3. Copy the contents of this project to mod/assign/submission/maharaws in your Moodle site.

4. Proceed with the plugin installation in Moodle.


Configure Mahara
--------------------

1. Enable web services:

      1.1 Navigate to 'Administration menu (🔧)-> Web services -> Configuration'.

      1.2 Enable 'Accept incoming web service requests'. 
      
      1.3 Enable the protocols REST and OAuth.

2. Generate a consumer key and consumer secret:

      2.1 Navigate to 'Administration menu (🔧) -> Web services -> External Apps'.

      2.2 Type a name for the application into the 'Application' text input, 
      e.g. Moodle. Applications are listed alphabetically. If you have multiple 
      institutions on your Mahara site, select the institution for which you 
      want to enable the connection.

      2.3 Select 'Moodle Assignment Submission; from the drop-down menu.

      2.4 Click 'Add'. You will then see the consumer key and secret. These 
      will be used when configuring the assignment activity in Moodle.

3. Enable account creation (optional):

      3.1 Click the 'Manage' button (cog icon) next to the newly created external application.

      3.2 Enable 'Auto-create accounts' and click the 'Save' button.


Create a new Mahara assignment in Moodle
------------------------------------------

1. Add an assignment in Moodle.

2. Select Mahara as the assignment submission type.

3. Fill in the following:
      - URL of your Mahara site.
      - Mahara web services OAuth key (from the external apps list in Mahara).
      - Mahara web services Oauth secret.



A little info about what it does
---------------------------------

This plugin requires your Moodle site to be connected to a Mahara site via
Mahara web services.

This plugin adds a 'Mahara' submission method to 'Assignment' activities in Moodle. 
When a teacher creates an such an activity, they will see 'Mahara' as one of the
submission method options. This submission method will ask students to select one
of their pages or collections from Mahara to include as part of their assignment
submission. 

* If you have not yet 'jumped' from Moodle to Mahara, you will need to do so. That
establishes the connection between the two platforms and allows you to view your portfolios
that you may have already created.
* Individual pages that are part of collections cannot be picked on their own. The
entire collection must be picked instead.
* Pages and collections that are already locked due to being submitted to a Mahara 
group or another Moodle assignment are also not available.

Optionally, the assignment may lock the submitted pages and collections
from being edited in Mahara. This is recommended because otherwise
students will be able to continue editing part of their assignment
submission even after the assignment deadline. The teacher may choose whether
submitted pages and collection will be unlocked after grading, or when the
grading workflow state changes to "Released".

If you choose to use locking, note that:
* Pages and collections that are part of a draft submission will not be locked until the draft is submitted.
* The Mahara pages will be locked if the submission is submitted OR the submission is 'locked via the Moodle gradebook.
* If a submission is 'reopened' via the Moodle gradebook, the page will become unlocked.

If the locking setting permits 'Unlock after grading':
* If the grading workflow is disabled, then grading the work will result in page unlocking.
* If the grading workflow is enabled, then page unlocking will happen at the point when the workflow state changes to 'Released', whether the submission has been graded or not prior to that.

If you need help, try the Moodle-Mahara Integration forum on mahara.org: https://mahara.org/interaction/forum/view.php?id=30

Bugs and improvements?
------------------------

If you have found a bug or if you have made an improvement to this plugin and want to share your code, please
open an issue in our [Github project](https://github.com/catalyst/assignsubmission-maharaws).

Credits
--------

This web services plugin is based on the [original one created for the connection via MNet](https://github.com/MaharaProject/moodle-assignsubmission_mahara).

The upgrade of the plugin to support web services has been done thanks to funding from

* Waitematā District Health Board
* Monash University
* Catalyst IT

License
-------

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, version 3 or later of the License.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
