import { RegisterItem } from '../../types/authentication/form';

const onSubmit: jest.Mock = jest.fn();

const initialsText: string = 'John Doe';
const emailText: string = 'johndoe@example.com';
const passwordText: string = 'Password123';

const handleFormSubmit: (data: RegisterItem) => void = (data: RegisterItem) => {
  const { FullName, Email, Password, Privacy } = data;

  if (!FullName || !Email || !Password || !Privacy) {
    return;
  }

  onSubmit(data);
};

describe('code snippet', () => {
  it('should call onSubmit function with valid data', () => {
    const data: RegisterItem = {
      FullName: initialsText,
      Email: emailText,
      Password: passwordText,
      Privacy: true,
    };

    handleFormSubmit(data);
    expect(onSubmit).toHaveBeenCalledWith(data);
  });

  it('should not call onSubmit function with invalid data', () => {
    const data: RegisterItem = {
      FullName: '',
      Email: emailText,
      Password: passwordText,
      Privacy: true,
    };

    handleFormSubmit(data);
    expect(onSubmit).not.toHaveBeenCalled();
  });
});
