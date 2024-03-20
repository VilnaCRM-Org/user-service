export interface ExpectationEmail {
  errorText: string;
  email: string;
}

export interface ExpectationsPassword {
  errorText: string;
  password: string;
}

export interface User {
  fullName: string;
  email: string;
  password: string;
}
