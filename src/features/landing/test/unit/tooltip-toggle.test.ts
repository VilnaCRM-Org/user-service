describe('handleTooltipClose and handleTooltipToggle', () => {
  let open: boolean;

  let setOpen: jest.Mock;

  beforeEach(() => {
    setOpen = jest.fn();
  });

  it('handleTooltipClose should set open to false', () => {
    const handleTooltipClose: () => void = () => {
      setOpen(false);
    };

    handleTooltipClose();
    expect(setOpen).toHaveBeenCalledWith(false);
  });

  it('handleTooltipToggle should toggle open state', () => {
    const handleTooltipToggle: () => void = () => {
      setOpen(!open);
    };

    handleTooltipToggle();
    expect(setOpen).toHaveBeenCalledWith(true);

    open = true;
    handleTooltipToggle();
    expect(setOpen).toHaveBeenCalledWith(false);
  });
});
