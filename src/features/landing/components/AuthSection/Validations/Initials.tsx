class FullNameValidator {
  public validateFullName(fullName: string): boolean {
    return (
      this.isValidFullNameFormat(fullName) && !this.hasEmptyParts(fullName)
    );
  }

  private isValidFullNameFormat(fullName: string): boolean {
    return /^[^\d\s]+\s[^\d\s]+$/.test(fullName);
  }

  private hasEmptyParts(fullName: string): boolean {
    return fullName.split(' ').some(part => part.length === 0);
  }
}

export default FullNameValidator;
