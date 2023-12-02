import { Typography, Box } from '@mui/material';
import React, { ChangeEvent, useState } from 'react';
import { useTranslation } from 'react-i18next';

interface ICustomInputProps {
  label: string;
  value: string;
  onChange: (event: ChangeEvent<HTMLInputElement>) => void;
  disabled?: boolean;
  id: string;
  placeholder: string;
  error?: string;
  style?: React.CSSProperties;
  type: string;
}

const styles = {
  mainBox: {
    width: '100%',
  },
  label: {
    color: '#404142',
    fontFamily: '"Inter-Regular", sans-serif',
    fontSize: '14px',
    fontStyle: 'normal',
    fontWeight: '500',
    lineHeight: '18px',
    marginBottom: '9px',
  },
  inputField: {
    width: '100%',
    maxWidth: '100%',
    color: '#969B9D',
    fontFamily: '"Inter-Regular", sans-serif',
    fontSize: '16px',
    fontStyle: 'normal',
    fontWeight: '400',
    lineHeight: '18px',
    borderRadius: '8px',
    padding: '23px 28px 23px 28px',
    outline: 'none',
    border: '1px solid #D0D4D8',
    marginTop: '9px',
  },
  inputFieldHover: {
    color: '#969B9D',
    border: '1px solid #969B9D',
  },
  inputFieldActive: {
    color: '#1A1C1E',
    border: '1px solid #57595B',
  },
  inputFieldDisabled: {
    color: '#969B9D',
    pointerEvents: 'none',
    backgroundColor: '#E1E7EA',
    border: '1px solid #E1E7EA',
  },
  errorMessage: {
    color: '#DC3939',
    fontFamily: '"Inter-Regular", sans-serif',
    fontSize: '14px',
    fontStyle: 'normal',
    fontWeight: '500',
    lineHeight: '18px',
    marginTop: '4px',
  },
};

export default function CustomInput({
  id,
  label,
  value,
  onChange,
  disabled = false,
  error = '',
  placeholder,
  style,
  type,
}: ICustomInputProps) {
  const { t } = useTranslation();
  const [isFocused, setIsFocused] = useState(false);
  const [isHovered, setIsHovered] = useState(false);

  const handleFocus = () => {
    setIsFocused(true);
  };

  const handleBlur = () => {
    setIsFocused(false);
  };

  const handleMouseEnter = () => {
    setIsHovered(true);
  };

  const handleMouseLeave = () => {
    setIsHovered(false);
  };

  return (
    <Box sx={{ ...styles.mainBox, ...style }}>
      <Typography
        variant="body1"
        component="label"
        htmlFor={id}
        style={{ ...styles.label, ...style }}
      >
        {t(label)}
      </Typography>
      <input
        id={id}
        value={value}
        onChange={onChange}
        onFocus={handleFocus}
        onBlur={handleBlur}
        onMouseEnter={handleMouseEnter}
        onMouseLeave={handleMouseLeave}
        disabled={disabled}
        type={type}
        placeholder={placeholder}
        style={{
          ...styles.inputField,
          ...(isHovered && !isFocused && !disabled && styles.inputFieldHover),
          ...(isFocused && styles.inputFieldActive),
          ...(disabled && { ...styles.inputFieldDisabled, pointerEvents: 'none' }),
        }}
      />
      {error && (
        <Typography component="p" variant="body1" style={{ ...styles.errorMessage }}>
          {error}
        </Typography>
      )}
    </Box>
  );
}

CustomInput.defaultProps = {
  disabled: false,
  error: null,
  style: {},
};
