<?php

/**
 * Auth pages (login, forgot/reset password). These render for guests, so they
 * live in the `user` namespace and are exposed to Svelte as `t('auth.*')`.
 */
return [

    'title' => 'Sign in',
    'subtitle' => 'Enter your credentials to access your account.',
    'email' => 'Email',
    'password' => 'Password',
    'password_placeholder' => 'Enter your password',
    'toggle_password' => 'Toggle password visibility',
    'remember' => 'Remember me',
    'forgot' => 'Forgot password?',
    'sign_in' => 'Sign in',
    'signing_in' => 'Signing in…',
    'or_continue' => 'or continue with',
    'azure' => 'Azure',
    'network_error' => 'Network error. Please try again.',

    'forgot_title' => 'Forgot your password?',
    'forgot_subtitle' => "Enter your email and we'll send you a reset link.",
    'send_link' => 'Send reset link',
    'sending' => 'Sending…',
    'back_to_login' => 'Back to sign in',
    'link_sent' => 'If that email exists, a reset link has been sent.',

    'reset_title' => 'Reset password',
    'reset_subtitle' => 'Choose a new password for your account.',
    'new_password' => 'New password',
    'confirm_password' => 'Confirm password',
    'reset_submit' => 'Reset password',
    'resetting' => 'Resetting…',
    'reset_done' => 'Your password has been reset. You can now sign in.',
];
