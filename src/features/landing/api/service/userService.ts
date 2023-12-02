const createUser = async (email: string, initials: string) => {
  await new Promise<void>((resolve) => {
    setTimeout(() => {
      resolve();
    }, 2000);
  });

  return { id: Math.random().toString(), initials, email };
};

export default createUser;
