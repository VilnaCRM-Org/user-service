import http from 'k6/http';

export default class AuthFlowUtils {
  constructor(utils) {
    this.utils = utils;
  }

  signIn(email, password, rememberMe = false) {
    const payload = JSON.stringify({
      email,
      password,
      rememberMe,
    });

    return this.post('/signin', payload, this.utils.getJsonHeader());
  }

  refreshToken(refreshToken) {
    const payload = JSON.stringify({
      refreshToken,
    });

    return this.post('/token', payload, this.utils.getJsonHeader());
  }

  setupTwoFactor(accessToken) {
    return this.post('/users/2fa/setup', JSON.stringify({}), this.utils.getJsonHeaderWithAuth(accessToken));
  }

  confirmTwoFactor(accessToken, twoFactorCode) {
    const payload = JSON.stringify({
      twoFactorCode,
    });

    return this.post('/users/2fa/confirm', payload, this.utils.getJsonHeaderWithAuth(accessToken));
  }

  disableTwoFactor(accessToken, twoFactorCode) {
    const payload = JSON.stringify({
      twoFactorCode,
    });

    return this.post('/users/2fa/disable', payload, this.utils.getJsonHeaderWithAuth(accessToken));
  }

  regenerateRecoveryCodes(accessToken) {
    return this.post('/users/2fa/recovery-codes', JSON.stringify({}), this.utils.getJsonHeaderWithAuth(accessToken));
  }

  completeTwoFactor(pendingSessionId, twoFactorCode) {
    const payload = JSON.stringify({
      pendingSessionId,
      twoFactorCode,
    });

    return this.post('/signin/2fa', payload, this.utils.getJsonHeader());
  }

  signOut(accessToken) {
    return this.post('/signout', JSON.stringify({}), this.utils.getJsonHeaderWithAuth(accessToken));
  }

  signOutAll(accessToken) {
    return this.post('/signout/all', JSON.stringify({}), this.utils.getJsonHeaderWithAuth(accessToken));
  }

  post(path, payload, params) {
    const response = http.post(`${this.utils.getBaseUrl()}${path}`, payload, params);

    return {
      response,
      body: this.parseJsonBody(response),
    };
  }

  parseJsonBody(response) {
    if (typeof response.body !== 'string' || response.body === '') {
      return null;
    }

    try {
      return JSON.parse(response.body);
    } catch (error) {
      return null;
    }
  }
}
