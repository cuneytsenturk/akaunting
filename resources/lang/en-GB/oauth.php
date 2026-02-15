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
    'select_company'                => 'Select Company',
    'company_selection_info'        => 'The token will be associated with the selected company and can only access data from that company.',

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

    // Authorized Applications
    'authorized_applications'       => 'Authorized Applications',
    'authorized_applications_description' => 'Manage third-party applications that have access to your account.',
    'no_authorized_applications'    => 'No Authorized Applications',
    'no_authorized_applications_description' => 'You haven\'t authorized any third-party applications yet.',
    'dynamic_client'                => 'Dynamic Client',
    'first_party'                   => 'First Party',
    'active_tokens_count'           => '{1} :count active token|[2,*] :count active tokens',
    'no_active_tokens'              => 'No active tokens',
    'manage_tokens'                 => 'Manage Tokens',
    'last_used'                     => 'Last used',
    'revoke_access'                 => 'Revoke Access',
    'confirm_revoke_all'            => 'Are you sure you want to revoke all tokens for this application?',
    'confirm_delete_client'         => 'Are you sure you want to delete this client?',
    'client_deleted'                => 'Client ":name" has been deleted.',
    'access_revoked'                => 'Access revoked for ":name" (:count tokens).',
    'security_notice'               => 'Security Notice',
    'security_notice_description'   => 'Review and manage applications that have access to your account. You can revoke access at any time.',
    'can_revoke_access'             => 'You can revoke access to this application at any time from your account settings.',
    'application_details'           => 'Application Details',
    'website'                       => 'Website',
    'privacy_policy'                => 'Privacy Policy',
    'view_privacy_policy'           => 'View Privacy Policy',
    'terms_of_service'              => 'Terms of Service',
    'view_terms'                    => 'View Terms',
    'confirm_deny'                  => 'Are you sure you want to deny this authorization request?',
    'authorize_prompt'              => 'Authorize :app_name?',
    'authorize_description'         => 'Logged in as :user',
    'permissions_requested'         => 'Permissions Requested',
    'deny'                          => 'Deny',

    // Scopes
    'scopes.mcp:use.name'           => 'MCP Access',
    'scopes.mcp:use.description'    => 'Access MCP server capabilities and interact with your data via Model Context Protocol',
    'scopes.read.name'              => 'Read Access',
    'scopes.read.description'       => 'Read your account data',
    'scopes.write.name'             => 'Write Access',
    'scopes.write.description'      => 'Create and modify your account data',
    'scopes.admin.name'             => 'Admin Access',
    'scopes.admin.description'      => 'Full administrative access to your account',

    // Messages
    'no_personal_access_client'     => 'No personal access client found. Please run passport:client --personal command first.',

];
