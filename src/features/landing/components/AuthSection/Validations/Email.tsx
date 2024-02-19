class EmailValidator {
  public validateEmail(email: string): boolean {
    return this.isValidEmailFormat(email);
  }

  public isValidEmailFormat(email: string): boolean {
    return /^.+@.+\..+$/.test(email);
  }
}
export default EmailValidator;
