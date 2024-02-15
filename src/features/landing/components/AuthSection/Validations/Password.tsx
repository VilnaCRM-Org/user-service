class PasswordValidator {
  public static isLengthValid(value: string): boolean {
    return value.length >= 8 && value.length <= 64;
  }

  public static hasNumber(value: string): boolean {
    return /[0-9]/.test(value);
  }

  public static hasUppercase(value: string): boolean {
    return /[A-Z]/.test(value);
  }

  public static validatePassword(value: string): boolean {
    return (
      PasswordValidator.isLengthValid(value) &&
      PasswordValidator.hasNumber(value) &&
      PasswordValidator.hasUppercase(value)
    );
  }
}

export default PasswordValidator;
