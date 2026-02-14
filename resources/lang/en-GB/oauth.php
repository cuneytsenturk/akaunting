<?php

return [

    // General
    'oauth'                         => 'OAuth',
    'clients'                       => 'OAuth Clients',
    'client'                        => 'OAuth Client',
    'tokens'                        => 'Access Tokens',
    'token'                         => 'Access Token',
    'personal_access_tokens'        => 'Personal Access Tokens',
    'scopes'                        => 'Scopes',
    'scope'                         => 'Scope',

    // Authorization
    'authorize'                     => 'Authorize',
    'authorize_application'         => 'Authorize Application',
    'authorize_title'               => 'Authorization Request',
    'requests_access'               => 'is requesting access to your account',
    'will_be_able_to'               => 'This application will be able to',
    'redirect_info'                 => 'You will be redirected to :url',

    // Client Management
    'client_id'                     => 'Client ID',
    'client_secret'                 => 'Client Secret',
    'client_name'                   => 'Client Name',
    'client_information'            => 'Client Information',
    'client_information_description' => 'Enter the basic information for your OAuth client application.',
    'client_name_placeholder'       => 'My Application',
    'redirect_url'                  => 'Redirect URL',
    'client_details'                => 'Client Details',
    'client_created'                => 'Client Created Successfully',
    'credentials_warning'           => 'Make sure to copy your client credentials now. You won\'t be able to see them again!',
    'confidential_client'           => 'Confidential Client',
    'confidential_client_description' => 'Require the client secret for authentication (recommended for server-side applications)',

    // Grant Types
    'grant_type'                    => 'Grant Type',
    'grant_types'                   => 'Grant Types',
    'grant_types_description'       => 'Select the OAuth grant types this client can use.',
    'grant_type_info'               => 'This client will use the Authorization Code grant type by default.',
    'authorization_code'            => 'Authorization Code',
    'password_grant'                => 'Password Grant',
    'personal_access'               => 'Personal Access',

    // Token Management
    'create_token'                  => 'Create Token',
    'token_name'                    => 'Token Name',
    'token_name_placeholder'        => 'My Application Token',
    'token_created'                 => 'Token Created Successfully',
    'token_warning'                 => 'Make sure to copy your personal access token now. You won\'t be able to see it again!',
    'token_copied'                  => 'Token copied to clipboard',
    'access_token'                  => 'Access Token',
    'new_client_secret'             => 'New Client Secret',
    'no_tokens'                     => 'You haven\'t created any personal access tokens yet.',
    'all_scopes'                    => 'All Scopes',
    'expires_at'                    => 'Expires At',
    'never'                         => 'Never',
    'active'                        => 'Active',
    'revoked'                       => 'Revoked',
    'revoke'                        => 'Revoke',

    // Secret Management
    'regenerate_secret'             => 'Regenerate Secret',
    'secret_regenerated'            => 'Secret Regenerated Successfully',
    'confirm_regenerate_secret'     => 'Are you sure you want to regenerate this client secret? All existing tokens using the old secret will stop working.',
    'secret_warning'                => 'The client secret will only be shown once. Make sure to copy it now!',
    'secret_hidden_message'         => 'The client secret is hidden for security. You can regenerate it if needed, but this will invalidate all existing tokens.',

    // Owner
    'owner'                         => 'Owner',

    // Messages
    'no_personal_access_client'     => 'No personal access client found. Please run passport:client --personal command first.',

];
