const createUser: (
  email: string,
  initials: string
) => Promise<{ id: string; initials: string; email: string }> = async (
  email,
  initials
) => {
  await new Promise<void>(resolve => {
    setTimeout(() => {
      resolve();
    }, 2000);
  });

  return { id: Math.random().toString(), initials, email };
};

export default createUser;
