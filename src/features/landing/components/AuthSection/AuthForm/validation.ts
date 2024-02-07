const isLengthValid: (value: string) => boolean = (value: string) =>
  value.length >= 8 && value.length <= 64;

const hasNumber: (value: string) => boolean = (value: string) =>
  /[0-9]/.test(value);

const hasUppercase: (value: string) => boolean = (value: string) =>
  /[A-Z]/.test(value);

export const validatePassword: (value: string) => boolean = (
  value: string
): boolean => isLengthValid(value) && hasNumber(value) && hasUppercase(value);

const isValidEmailFormat: (email: string) => boolean = (email: string) =>
  /^.+@.+\..+$/.test(email);

export const validateEmail: (email: string) => boolean = (
  email: string
): boolean => isValidEmailFormat(email);

const isValidFullNameFormat: (fullName: string) => boolean = (
  fullName: string
) => /^[^\d\s]+\s[^\d\s]+$/.test(fullName);

const hasEmptyParts: (fullName: string) => boolean = (fullName: string) =>
  fullName.split(' ').some(part => part.length === 0);

export const validateFullName: (fullName: string) => boolean = (
  fullName: string
) => isValidFullNameFormat(fullName) && !hasEmptyParts(fullName);
