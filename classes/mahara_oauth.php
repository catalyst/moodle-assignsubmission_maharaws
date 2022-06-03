<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * This file contains the definition for the library class for Mahara submission plugin
 *
 * This class provides all the functionality for the new assign module.
 *
 * @package   assignsubmission_maharaws
 * @copyright 2020 Catalyst IT
 * @copyright 2012 Lancaster University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace assignsubmission_maharaws;
defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/oauthlib.php');

/**
 * This class mahara_oauth.
 *
 * @package   assignsubmission_maharaws
 * @copyright 2020 Catalyst IT
 * @copyright 2012 Lancaster University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mahara_oauth extends \oauth_helper {

    /**
     * Request oauth protected resources
     *
     * @param string   $method
     * @param string   $url
     * @param string[] $params
     * @param string   $token
     * @param string   $secret
     */
    public function request($method, $url, $params=array(), $token='', $secret='') {
        $token = '';
        $this->sign_secret = $secret.'&'.$token;  // We never pass the token, only the secret.
        if (strtolower($method) === 'post' && !empty($params)) {
            $oauthparams = $this->prepare_oauth_parameters($url, array('oauth_token' => $token) + $params, $method);
        } else {
            $oauthparams = $this->prepare_oauth_parameters($url, array('oauth_token' => $token), $method);
        }
        $this->setup_oauth_http_header($oauthparams);
        $content = call_user_func_array(array($this->http, strtolower($method)), array($url, $params, $this->http_options));

        if ($this->http->info['http_code'] != 200) {
            throw new \moodle_exception('webservice call was not successful');
        }

        // Reset http header and options to prepare for the next request.
        $this->http->resetHeader();
        // Return request return value.
        return $content;
    }
}
