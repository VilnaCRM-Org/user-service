import React, { CSSProperties, useState } from 'react';
import { ButtonProps } from '@mui/material';

const DEFAULT_BUTTON_BORDER_RADIUS = '5.7rem';

enum ButtonBackgroundEnum {
  DEFAULT_BLUE = '#1EAEFF',
  DEFAULT_ACTIVE = '#0399ED',
  DEFAULT_HOVER = '#00A3FF',
  DEFAULT_DISABLED = '#E1E7EA',
  WHITE = '#FFF',
  WHITE_ACTIVE = '#FFF',
  WHITE_HOVER = '#EAECEE',
  WHITE_DISABLED = '#E1E7EA',
}

enum ButtonBorderColorEnum {
  DEFAULT = '#1EAEFF',
  DEFAULT_ACTIVE = '#0399ED',
  DEFAULT_HOVER = '#00A3FF',
  DEFAULT_DISABLED = '#E1E7EA',
  WHITE = '#969B9D',
  WHITE_ACTIVE = '#EAECEE',
  WHITE_HOVER = '#EAECEE',
  WHITE_DISABLED = '#E1E7EA',
}

enum ButtonColorEnum {
  DEFAULT = '#FFFFFF',
  DEFAULT_ACTIVE = '#FFFFFF',
  DEFAULT_HOVER = '#FFFFFF',
  DEFAULT_DISABLED = '#FFFFFF',
  WHITE = '#1B2327',
  WHITE_ACTIVE = '#1B2327',
  WHITE_HOVER = '#1B2327',
  WHITE_DISABLED = '#FFFFFF',
}

interface IButtonProps extends ButtonProps {
  customVariant: 'light-blue' | 'transparent-white';
  buttonSize?: 'big' | 'medium';
  isDisabled?: boolean;
  className?: string;
  fullWidth?: boolean;
  onClick: () => void;
  style?: CSSProperties;
}

export function Button({
  children,
  customVariant,
  isDisabled = false,
  buttonSize = 'medium',
  onClick,
  fullWidth,
  style,
  ...props
}: IButtonProps) {
  const [isHovered, setIsHovered] = useState(false);
  const [isActive, setIsActive] = useState(false);

  const handleButtonClick = () => {
    onClick();
  };

  // if by default custom variant is light blue, apply default styles
  let buttonStyle: React.CSSProperties = {
    width: fullWidth ? '100%' : 'auto',
    backgroundColor: ButtonBackgroundEnum.DEFAULT_BLUE,
    borderRadius: DEFAULT_BUTTON_BORDER_RADIUS,
    padding: buttonSize === 'medium' ? '16px 24px' : '20px 30px',
    fontFamily: "'GolosText-Regular', sans-serif",
    fontSize: '15px',
    fontStyle: 'normal',
    fontWeight: 500,
    lineHeight: '18px',
    color: ButtonColorEnum.DEFAULT,
    outline: 'none',
    border: `1px solid ${ButtonBorderColorEnum.DEFAULT}`,
    ...style,
  };

  let hoverStyle: React.CSSProperties = {
    cursor: 'pointer',
    transition: 'all 0.3s ease-in',
    color: ButtonColorEnum.DEFAULT_HOVER,
    backgroundColor: ButtonBackgroundEnum.DEFAULT_HOVER,
    border: `1px solid ${ButtonBorderColorEnum.DEFAULT_HOVER}`,
  };

  let activeStyle: React.CSSProperties = {
    cursor: 'pointer',
    transition: 'all 0.3s ease-in',
    color: ButtonColorEnum.DEFAULT_ACTIVE,
    backgroundColor: ButtonBackgroundEnum.DEFAULT_ACTIVE,
    border: `1px solid ${ButtonBorderColorEnum.DEFAULT_ACTIVE}`,
  };

  let disabledStyle: React.CSSProperties = {
    cursor: 'not-allowed',
    transition: 'all 0.3s ease-in',
    color: ButtonColorEnum.DEFAULT_DISABLED,
    backgroundColor: ButtonBackgroundEnum.DEFAULT_DISABLED,
    border: `1px solid ${ButtonBorderColorEnum.DEFAULT_DISABLED}`,
  };

  // if button is white, apply styles
  if (customVariant === 'transparent-white') {
    buttonStyle = {
      ...buttonStyle,
      backgroundColor: ButtonBackgroundEnum.WHITE,
      color: ButtonColorEnum.WHITE,
      border: `1px solid ${ButtonBorderColorEnum.WHITE}`,
      outline: 'none',
    };

    hoverStyle = {
      ...hoverStyle,
      color: ButtonColorEnum.WHITE_HOVER,
      backgroundColor: ButtonBackgroundEnum.WHITE_HOVER,
      border: `1px solid ${ButtonBorderColorEnum.WHITE_HOVER}`,
    };

    activeStyle = {
      ...activeStyle,
      color: ButtonColorEnum.WHITE_ACTIVE,
      backgroundColor: ButtonBackgroundEnum.WHITE_ACTIVE,
      border: `1px solid ${ButtonBorderColorEnum.WHITE_ACTIVE}`,
    };

    disabledStyle = {
      ...disabledStyle,
      color: ButtonColorEnum.WHITE_DISABLED,
      backgroundColor: ButtonBackgroundEnum.WHITE_DISABLED,
      border: `1px solid ${ButtonBorderColorEnum.WHITE_DISABLED}`,
    };
  }

  return (
    <button
      {...props}
      style={{
        ...buttonStyle,
        ...(isHovered && hoverStyle),
        ...(isActive && activeStyle),
        ...(isDisabled && disabledStyle),
      }}
      onMouseEnter={() => setIsHovered(true)}
      onMouseLeave={() => setIsHovered(false)}
      onMouseDown={() => setIsActive(true)}
      onMouseUp={() => setIsActive(false)}
      onClick={isDisabled ? undefined : handleButtonClick}
      disabled={isDisabled}
    >
      {children}
    </button>
  );
}
