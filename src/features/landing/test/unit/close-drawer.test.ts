const setIsDrawerOpen: jest.Mock = jest.fn();

function handleCloseDrawer(): void {
  setIsDrawerOpen(false);
}

describe('handleCloseDrawer', () => {
  beforeEach(() => {
    setIsDrawerOpen.mockClear();
  });

  it('should call setIsDrawerOpen with false', () => {
    handleCloseDrawer();

    expect(setIsDrawerOpen).toHaveBeenCalledWith(false);
  });
});
