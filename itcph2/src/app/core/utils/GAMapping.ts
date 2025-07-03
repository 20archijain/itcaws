export const GA_ROUTE_MAPPING = {
  forgot: {
    name: 'Forgot'
  },
  login: {
    name: 'Login'
  },
  logout: {
    name: 'Logout'
  }
};

export const GA_ACTION_LIST = {
  auth: {
    forgot: {
      apiFailed: 'forgot_failed',
      apiSuccess: 'forgot_success',
      category: 'Forgot Password',
      label: 'Forgot btn click',
    },
    login: {
      apiFailed: 'login_failed',
      apiSuccess: 'login_success',
      category: 'Login',
      label: 'Login btn click',
    },
    logout: {
      apiFailed: 'logout_failed',
      apiSuccess: 'logout_success',
      category: 'Logout',
      label: 'Logout Btn Click',
      timeoutLabel: 'Logout after Timeout',
    },
    twoWayResendOtpBtn: {
      apiFailed: 'otp_resend_failed',
      apiSuccess: 'otp_resend_success',
      category: 'Two Way Auth',
      label: 'Resend btn click',
    },
    twoWayVerifyOtpBtn: {
      apiFailed: 'otp_verify_failed',
      apiSuccess: 'otp_verify_success',
      category: 'Two Way Auth',
      label: 'Verify btn click',
    },
  },
};
