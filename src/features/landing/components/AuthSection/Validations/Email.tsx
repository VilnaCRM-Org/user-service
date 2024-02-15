class EmailValidator {
  public static validateEmail(email: string): boolean {
    return EmailValidator.isValidEmailFormat(email);
  }

  private static isValidEmailFormat(email: string): boolean {
    return /^.+@.+\..+$/.test(email);
  }
}

export default EmailValidator;
