import { ButtonProps } from '@mui/material';
import React, { CSSProperties, useState } from 'react';

import ButtonBackgroundEnum from '@/types/Button/ButtonBackgroundEnum';
import ButtonBorderColorEnum from '@/types/Button/ButtonBorderColorEnum';
import ButtonColorEnum from '@/types/Button/ButtonColorEnum';

const DEFAULT_BUTTON_BORDER_RADIUS = '5.7rem';

interface IButtonProps extends ButtonProps {
  customVariant: 'light-blue' | 'transparent-white';
  buttonSize?: 'big' | 'medium';
  isDisabled?: boolean;
  className?: string;
  fullWidth?: boolean;
  onClick?: () => void;
  style?: CSSProperties;
  type?: 'button' | 'submit' | 'reset';
}

export default function Button({
  children,
  customVariant,
  isDisabled = false,
  buttonSize = 'medium',
  onClick,
  fullWidth,
  style,
  type = 'button',
  className,
}: IButtonProps) {
  const [isHovered, setIsHovered] = useState(false);
  const [isActive, setIsActive] = useState(false);

  const handleButtonClick = () => {
    if (onClick) {
      onClick();
    }
  };

  // if by default custom variant is light blue, apply default styles
  let buttonStyle: React.CSSProperties = {
    width: fullWidth ? '100%' : 'auto',
    backgroundColor: ButtonBackgroundEnum.DEFAULT_BLUE,
    borderRadius: DEFAULT_BUTTON_BORDER_RADIUS,
    padding: buttonSize === 'medium' ? '16px 24px' : '20px 30px',
    fontFamily: "'GolosText-Regular', sans-serif",
    fontSize: buttonSize === 'big' ? '18px' : '15px',
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
      type={type === 'submit' ? 'submit' : 'button'}
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
      className={className}
    >
      {children}
    </button>
  );
}

Button.defaultProps = {
  buttonSize: 'medium',
  isDisabled: false,
  className: '',
  fullWidth: false,
  style: {},
  type: 'button',
  onClick: () => {},
};
