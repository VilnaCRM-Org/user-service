const isLengthValid = (value: string): boolean =>
  value.length >= 8 && value.length <= 64;

const hasNumber = (value: string): boolean => /[0-9]/.test(value);

const hasUppercase = (value: string): boolean => /[A-Z]/.test(value);

export const validatePassword = (value: string): string | boolean => {
  if (!isLengthValid(value))
    return 'Password must be between 8 and 64 characters long';
  if (!hasNumber(value)) return 'Password must contain at least one number';
  if (!hasUppercase(value))
    return 'Password must contain at least one uppercase letter';
  return true;
};

const isValidEmailFormat = (email: string): boolean =>
  /^.+@.+\..+$/.test(email);

export const validateEmail = (email: string): string | boolean => {
  if (!isValidEmailFormat(email)) return 'Invalid email format';
  return true;
};

const isValidFullNameFormat = (fullName: string): boolean =>
  /^[^\d\s]+\s[^\d\s]+$/.test(fullName);

const hasEmptyParts = (fullName: string): boolean =>
  fullName.split(' ').some(part => part.length === 0);

export const validateFullName = (fullName: string): string | boolean => {
  if (!isValidFullNameFormat(fullName)) return 'Invalid full name format';
  if (hasEmptyParts(fullName))
    return 'Name and surname should have at least 1 character';
  return true;
};
