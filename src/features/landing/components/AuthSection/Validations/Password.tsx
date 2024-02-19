class PasswordValidator {
  public isLengthValid(value: string): boolean {
    return value.length >= 8 && value.length <= 64;
  }

  public hasNumber(value: string): boolean {
    return /[0-9]/.test(value);
  }

  public hasUppercase(value: string): boolean {
    return /[A-Z]/.test(value);
  }

  public validatePassword(value: string): boolean {
    return (
      this.isLengthValid(value) &&
      this.hasNumber(value) &&
      this.hasUppercase(value)
    );
  }
}

export default PasswordValidator;
