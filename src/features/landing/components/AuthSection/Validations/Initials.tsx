class FullNameValidator {
  public static validateFullName(fullName: string): boolean {
    return (
      FullNameValidator.isValidFullNameFormat(fullName) &&
      !FullNameValidator.hasEmptyParts(fullName)
    );
  }

  private static isValidFullNameFormat(fullName: string): boolean {
    return /^[^\d\s]+\s[^\d\s]+$/.test(fullName);
  }

  private static hasEmptyParts(fullName: string): boolean {
    return fullName.split(' ').some(part => part.length === 0);
  }
}

export default FullNameValidator;
