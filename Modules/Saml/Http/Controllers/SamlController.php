<?php

namespace Modules\Saml\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use App\Email;
use App\User;
use Session;
use OneLogin\Saml2\Auth as OneLogin_Saml2_Auth;

class SamlController extends Controller
{
    public function acsEndpoing(Request $request, $secret)
    {
        \Saml::debug("(ACS endpoint) Starting processing request from IdP...");

        $this->checkSecret($secret);

        $settings = \Saml::getSettings();
        $request_id = session()->get('saml.request_id');

        if (empty($request_id)) {
            \Saml::log("(ACS endpoint) Missing request_id in session.");
            \Helper::addFloatingFlash(__('Error occurred during SAML SSO authentication: :error', ['error' => 'Missing request_id in session']));
            return redirect()->route('login');
        }

        \Saml::debug("(ACS endpoint) IdP redirected to ACS endpoint.", ['request_id' => $request_id]);

        $auth = new OneLogin_Saml2_Auth(\Saml::getSamlConfig());
        $auth->processResponse($request_id);

        $errors = $auth->getErrors();

        if (!empty($errors)) {
            \Saml::log("(ACS endpoint) Error processing request from IdP.", ['errors' => $errors, 'lastReason' => $auth->getLastErrorReason()]);
            \Helper::addFloatingFlash(__('Error occurred during SAML SSO authentication: :error', ['error' => $auth->getLastErrorReason()]));
            return redirect()->route('login');
        }

        if (!$auth->isAuthenticated()) {
            \Saml::debug("(ACS endpoint) User is not authenticated in IdP.");
            \Helper::addFloatingFlash(__('SSO: Not authenticated.'));
            return redirect()->route('login');
        }

        $name_id = $auth->getNameId();

        \Saml::debug("(ACS endpoint) Received the following NameId from IdP.", ['NameId' => $name_id]);

        $email = Email::sanitizeEmail($name_id);
        $user = null;
        $attributes = $auth->getAttributes();

        \Saml::debug("(ACS endpoint) IdP user attributes: ", ['attributes' => $attributes]);

        if ($email) {
            $user = User::where('email', $email)->first();
        } else {
            \Saml::debug("(ACS endpoint) IdP passed something other than email in NameId: ".$name_id.". Trying to find an email among user attributes (email, emailaddress, emailAddress, email_address or mail). ");
            // Try to find an email among other attributes.
            foreach ($attributes as $attr_name => $attr_values) {
                // http://schemas.xmlsoap.org/ws/2005/05/identity/claims/emailaddress
                $attr_name = \Saml::sanitizeIdpAttrName($attr_name);

                if (in_array(strtolower($attr_name), ['email', 'emailaddress', 'email_address', 'mail']) && !empty($attr_values[0])) {
                    $email = Email::sanitizeEmail($attr_values[0]);
                    if ($email) {
                        $user = User::where('email', $email)->first();
                        if ($user) {
                            \Saml::debug("(ACS endpoint) Found an email in the following attribute.", array($attr_name => $email));
                            break;
                        } else {
                            \Saml::debug("(ACS endpoint) Found an email in the following attribute, there is no user with such email.", array($attr_name => $email));
                        }
                    }
                }
            }
        }

		if (!$user && !$settings['saml.auto_create_users']) {
            // User does not exist.
            \Saml::log("(ACS endpoint) IdP user does not exists in FreeScout. Enable users auto-creation to create users automatically.", ['NameId' => $name_id, 'attributes' => $attributes]);
            \Helper::addFloatingFlash(__('SSO user with email address :email does not exist. Please ask your administrator to create an account for you.', ['email' => $name_id]));
            return redirect()->route('login');

		} else if (!$user && $email) {
            // Create new user.
            // At this point $email contains email from NameId or email from one of attributes.
            \Saml::log("(ACS endpoint) Creating a new user", array('email' => $email));

            $user_data = [
                'email' => $email,
                'password' => User::generateRandomPassword(),
                'first_name' => 'SAML',
                'last_name' => 'User',
            ];

            // Map fields.
            if (!empty($settings['saml.mapping'])) {
                $mapping_lines = preg_split('/\r\n|\r|\n/', $settings['saml.mapping']);
                foreach ($mapping_lines as $mapping_line) {
                    $mapping = explode('>>', $mapping_line);
                    if (count($mapping) != 2) {
                        continue;
                    }
                    $idp_attr = trim($mapping[0]);
                    $fs_field = trim($mapping[1]);

                    $idp_attr = \Saml::sanitizeIdpAttrName($idp_attr);
                    if (!in_array($fs_field, \Saml::getMappableFields())) {
                        continue;
                    }
                    foreach ($attributes as $attr_name => $attr_values) {
                        $attr_name = \Saml::sanitizeIdpAttrName($attr_name);
                        if (strtolower($attr_name) == $idp_attr) {
                            if (isset($attr_values[0])) {
                                $user_data[$fs_field] = $attr_values[0];
                            }
                            break;
                        }
                    }
                }
            }
            \Saml::debug("(ACS endpoint) Mapping configuration:", array('mapping' => $settings['saml.mapping']));
            \Saml::debug("(ACS endpoint) User data after mapping:", array('user_data' => $user_data));

            $user = User::create($user_data);

            if ($user) {
                Auth::login($user);
                return redirect($request->session()->get('url.intended', '/'));
            } else {
                \Saml::log("(ACS endpoint) Error occurred creating a user.", ['email' => $email, 'NameId' => $name_id, 'attributes' => $attributes]);
                \Helper::addFloatingFlash(__('Error occurred creating an SSO user.', ['email' => $email]));
                return redirect()->route('login');
            }

        } elseif ($user->isDeleted()) {
            // User deleted.
            \Saml::log("(ACS endpoint) Deleted user tried to sign in.", ['email' => $name_id]);
            \Helper::addFloatingFlash(__('SSO user with email address :email does not exist. Please ask your administrator to create an account for you.', ['email' => $name_id]));
            return redirect()->route('login');
        } else {
            // Authenticate user.
            \Saml::debug("(ACS endpoint) User found and authenticated.", array('email' => $name_id));
            Auth::login($user);
            return redirect($request->session()->get('url.intended', '/'));
        }
    }

    public function metadata(Request $request, $secret)
    {
        $this->checkSecret($secret);

        try {
            $auth = new OneLogin_Saml2_Auth(\Saml::getSamlConfig());
            $settings = $auth->getSettings();
            $metadata = $settings->getSPMetadata();
            $errors = $settings->validateMetadata($metadata);
            if (empty($errors)) {
                header('Content-Type: text/xml');
                echo $metadata;
            } else {
                throw new OneLogin_Saml2_Error(
                    'Invalid SP metadata: '.implode(', ', $errors),
                    OneLogin_Saml2_Error::METADATA_SP_INVALID
                );
            }
        } catch (Exception $e) {
            //\Saml::log($e->getMessage());
        }
        exit();
    }

    public function checkSecret($secret)
    {
        if ($secret != \Saml::getSecret()) {
            \Saml::debug('Invalid secret received.', ['secret' => $secret, 'URL' => url()->full()]);
            abort(403, 'Invalid secret.');
        }
    }

    public function singleLogout(Request $request, $secret)
    {
        $this->checkSecret($secret);

        $auth = new OneLogin_Saml2_Auth(\Saml::getSamlConfig());
        $auth->processSLO();

        $errors = $auth->getErrors();

        if (empty($errors)) {
            return redirect()->route('login');
        } else {
            \Saml::log('(Single Logout) .', ['errors' => $errors]);
            return redirect()->route('login');
        }
    }
}
