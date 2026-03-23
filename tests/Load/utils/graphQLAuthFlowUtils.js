import http from 'k6/http';

export default class GraphQLAuthFlowUtils {
  constructor(utils) {
    this.utils = utils;
  }

  signIn(email, password) {
    const mutation = `mutation { signInUser(input: { email: "${email}", password: "${password}" }) { user { success twoFactorEnabled accessToken refreshToken pendingSessionId } } }`;

    return this.post(mutation);
  }

  completeTwoFactor(pendingSessionId, twoFactorCode) {
    const mutation = `mutation { completeTwoFactorUser(input: { pendingSessionId: "${pendingSessionId}", twoFactorCode: "${twoFactorCode}" }) { user { success twoFactorEnabled accessToken refreshToken recoveryCodesRemaining warning } } }`;

    return this.post(mutation);
  }

  refreshToken(refreshToken) {
    const mutation = `mutation { refreshTokenUser(input: { refreshToken: "${refreshToken}" }) { user { success accessToken refreshToken } } }`;

    return this.post(mutation);
  }

  setupTwoFactor(accessToken) {
    const mutation = `mutation { setupTwoFactorUser(input: {}) { user { success otpauthUri secret } } }`;

    return this.postWithAuth(mutation, accessToken);
  }

  confirmTwoFactor(accessToken, twoFactorCode) {
    const mutation = `mutation { confirmTwoFactorUser(input: { twoFactorCode: "${twoFactorCode}" }) { user { success recoveryCodes } } }`;

    return this.postWithAuth(mutation, accessToken);
  }

  disableTwoFactor(accessToken, twoFactorCode) {
    const mutation = `mutation { disableTwoFactorUser(input: { twoFactorCode: "${twoFactorCode}" }) { user { success } } }`;

    return this.postWithAuth(mutation, accessToken);
  }

  regenerateRecoveryCodes(accessToken) {
    const mutation = `mutation { regenerateRecoveryCodesUser(input: {}) { user { success recoveryCodes } } }`;

    return this.postWithAuth(mutation, accessToken);
  }

  signOut(accessToken) {
    const mutation = `mutation { signOutUser(input: {}) { user { success } } }`;

    return this.postWithAuth(mutation, accessToken);
  }

  signOutAll(accessToken) {
    const mutation = `mutation { signOutAllUser(input: {}) { user { success } } }`;

    return this.postWithAuth(mutation, accessToken);
  }

  requestPasswordReset(email) {
    const mutation = `mutation { requestPasswordResetUser(input: { email: "${email}" }) { user { id } } }`;

    return this.post(mutation);
  }

  confirmPasswordReset(token, newPassword) {
    const mutation = `mutation { confirmPasswordResetUser(input: { token: "${token}", newPassword: "${newPassword}" }) { user { id } } }`;

    return this.post(mutation);
  }

  post(mutation) {
    const response = http.post(
      this.utils.getBaseGraphQLUrl(),
      JSON.stringify({ query: mutation }),
      this.utils.getJsonHeader()
    );

    return {
      response,
      body: this.parseBody(response),
    };
  }

  postWithAuth(mutation, accessToken) {
    const response = http.post(
      this.utils.getBaseGraphQLUrl(),
      JSON.stringify({ query: mutation }),
      this.utils.getJsonHeaderWithAuth(accessToken)
    );

    return {
      response,
      body: this.parseBody(response),
    };
  }

  parseBody(response) {
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
