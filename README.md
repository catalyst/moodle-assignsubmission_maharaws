![Build Status](https://github.com/catalyst/assignsubmission-maharaws/actions/workflows/ci.yml/badge.svg?branch=main)

Mahara assignment submission plugin for Moodle
===============================================

Mahara assignment submission plugin for Moodle 3.9+
https://github.com/catalyst/assignsubmission-maharaws

This plugin adds Mahara portfolio submission functionality to assignments in
Moodle. It requires configuration of Mahara web services for REST and OAuth 1.x.

This plugin allows a teacher to add a 'Mahara' item to the submission
options in a Moodle assignment. Students can then select one of the pages
or collections from their personal Mahara account as part of their assignment
submission.

The submitted Mahara page or collection can be locked from editing in
Mahara if you wish, the same as if it had been submitted to a Mahara group.
Depending on settings, it may be left permanently locked or get unlocked
when the submission has been graded (or if the grading workflow is used, its state
changed to "Released"). Alternatively, portfolios can also be submitted without
locking. If portfolios are locked, they can be archived automatically in Mahara once
grading has finished.

The plugin requires that Mahara 20.10 or newer is installed. If you are using 
Mahara 20.04 or earlier (not supported with security fixes by the project any more),
apply the patches from the [issue report](https://bugs.launchpad.net/mahara/+bug/1882461).


Branches
--------

| Moodle version     | Mahara version  | Branch  |
| ----------------- | ---------------- | ---- |
| Moodle 3.9+       | Mahara 20.10+ | main |



Install the Moodle plugin
--------------------------------------------------

1. Make sure that your Moodle and Mahara versions are up to date.

2. Copy the contents of this project to mod/assign/submission/maharaws in your Moodle site.

4. Proceed with the plugin installation in Moodle.


Configure Mahara
--------------------

1. Configure LTI in Mahara:

      1.1 Navigate to 'Administration menu (🔧) -> Extension -> Plugin administration
       -> lti'.
      
      1.2 Select to 'Auto-configure LTI' and save the form. The necessary web services
        will be activated.

2. Generate a consumer key and consumer secret:

      2.1 Navigate to 'Administration menu (🔧) -> Web services -> External apps'.

      2.2 Type a name for the application into the 'Application' text input, 
      e.g. Moodle. Applications are listed alphabetically. If you have multiple 
      institutions on your Mahara site, select the institution for which you 
      want to enable the connection.

      2.3 Select 'Moodle Assignment Submission' from the drop-down menu.

      2.4 Click 'Add'. You will then see the consumer key and secret. These 
      will be used when configuring the assignment activity in Moodle.

3. Enable account creation (optional):

      3.1 Click the 'Manage' button (cog icon) next to the newly created external application.

      3.2 Enable 'Auto-create accounts' and click the 'Save' button.

4. Set a parent authentication method (optional):

      4.1 Click the 'Manage' button (cog icon) next to the newly created external application.

      4.2 Set the authentication method that your institution members shall also be able to 
      use and click the 'Save' button.

Configure Moodle
----------------

1. Go to Site Administration / Plugins / Activity Modules / Assignment / Submission Plugins / mahara Submissions

2. Set defaults for whether the option to submit from Mahara should always be enabled and the default state of 'Lock submitted pages'.

3. If you are only going to use one target instance of Mahara and you wish to set Mahara connection details only once, fill in the rest of the form. These values will default each time:
    - URL of your Mahara site.
    - Mahara web services OAuth key (the 'Consumer key' from the external apps list in Mahara).
    - Mahara web services Oauth secret (the 'Consumer secret' from the external apps list in Mahara).
4. If you select 'Force global credentials' then these credentials will always be used and course creators will not be able to override them on individual assignments. At the same time, you won't need to provide these details to course creators.
  
Create a new Mahara assignment in Moodle
------------------------------------------
**Note**: Users must have _assignsubmission/maharaws:configure_ capability to be able to configure URL, OAuth key, and secret.

1. Add an assignment in Moodle.

2. Select Mahara as the assignment submission type.

3. if you have not chosen to use global connection details, fill in the following:
      - URL of your Mahara site.
      - Mahara web services OAuth key (the 'Consumer key' from the external apps list in Mahara).
      - Mahara web services Oauth secret (the 'Consumer secret' from the external apps list in Mahara).

A little info about what it does
---------------------------------

This plugin requires your Moodle site to be connected to a Mahara site via
Mahara web services.

This plugin adds a 'Mahara' submission method to 'Assignment' activities in Moodle. 
When a teacher creates such an activity, they will see 'Mahara' as one of the
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
* The Mahara pages will be locked if the submission is submitted OR the submission is locked via the Moodle gradebook.
* If a submission is 'reopened' via the Moodle gradebook, the page will become unlocked.

If the locking setting permits 'Unlock after grading':
* If the grading workflow is disabled, then grading the work will result in page unlocking.
* If the grading workflow is enabled, then page unlocking will happen at the point when the workflow state changes to 'Released', whether the submission has been graded or not prior to that.

If you need help, try the [Moodle-Mahara Integration forum](https://mahara.org/interaction/forum/view.php?id=30)

Submitting of group portfolios is not yet supported.

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
