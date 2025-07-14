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
 * Web service class.
 *
 * @package     assignsubmission_maharaws
 * @author      2025 Sarah Cotton <sarah.cotton@catalyst-au.net>
 * @copyright   Catalyst IT, 2025
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * */

namespace assignsubmission_maharaws;

use stdClass;
use Exception;

/**
 * Web service class.
 */
class webservice {
    /**
     * Call the Mahara web service.
     *
     * @param string $function The Mahara web service function being called.
     * @param array $params The data being updated in Mahara.
     * @param stdClass $config Contains connection details.
     * @param string $method
     * @return mixed
     */
    public static function call(
        string $function,
        array $params,
        stdClass $config,
        string $method = "POST"
    ): mixed {
        global $CFG;

        if (empty($config->url)) {
            throw new Exception("The Mahara URL is not set correctly.");
        }
        if (empty($config->key)) {
            throw new Exception("The Mahara Key is not set correctly.");
        }
        if (empty($config->secret)) {
            throw new Exception("The Mahara secret is not set correctly.");
        }

        $endpoint = $config->url .
            (preg_match('/\/$/', $config->url) ? '' : '/') . // Append a trailing slash if $url doesn't already have one.
            'webservice/rest/server.php';
        $args = [
            'oauth_consumer_key' => $config->key,
            'oauth_consumer_secret' => $config->secret,
            'oauth_callback' => 'about:blank',
            'api_root' => $endpoint,
        ];

        $client = new mahara_oauth($args);
        if (!empty($CFG->disablesslchecks)) {
            $options = ['CURLOPT_SSL_VERIFYPEER' => 0, 'CURLOPT_SSL_VERIFYHOST' => 0];
            $client->setup_oauth_http_options($options);
        }
        // Have to flatten nested parameters into JSON as OAuth can't handle it.
        foreach ($params as $k => $v) {
            if (is_array($v)) {
                $params[$k] = json_encode($v);
            }
        }
        $content = $client->request(
            $method,
            $endpoint,
            array_merge($params, ['wsfunction' => $function, 'alt' => 'json']),
            null,
            $config->secret
        );
        $data = json_decode($content, true);

        if (isset($data['error']) && $data['error'] == true) {
            throw new Exception($data['error_rendered']);
        }
        return $data;
    }
}
