import { Box } from '@mui/material';
import React, { useState } from 'react';

interface ICustomCheckboxProps {
  id: string;
  children: React.ReactNode;
  checked: boolean;
  onChange: (checked: boolean) => void;
}

const styles = {
  mainBox: {
    display: 'flex',
    alignItems: 'center',
    '& input[type="checkbox"]': {
      cursor: 'pointer',
      opacity: '0',
      position: 'absolute',
    },
    '& label': {
      cursor: 'pointer',
      display: 'flex',
      color: '#404142',
      fontFamily: '"Inter-Regular", sans-serif',
      fontSize: '14px',
      fontStyle: 'normal',
      fontWeight: '500',
      lineHeight: '18px',
    },
    '& label a': {
      color: '#1EAEFF',
      fontFamily: 'Inter-Regular, sans-serif',
      fontSize: '14px',
      fontStyle: 'normal',
      fontWeight: '700',
      lineHeight: '18px',
      textDecorationLine: 'underline',
    },
    '& div': {
      width: '24px',
      height: '24px',
      borderRadius: '8px',
      border: '1px solid #D0D4D8',
      background: '#FFF',
      marginRight: '13px',
      flexShrink: '0',
    },
    '& label:hover::before': {
      border: '1px solid #1EAEFF',
    },
    '& input[type="checkbox"]:hover + div': {
      border: '1px solid #1EAEFF',
    },
    '& input[type="checkbox"]:checked + div': {
      backgroundImage: 'url("/assets/svg/checkbox-checked.svg")',
      backgroundPosition: 'center',
      backgroundColor: '#1EAEFF',
      backgroundRepeat: 'no-repeat',
      display: 'flex',
      justifyContent: 'center',
      alignItems: 'center',
      color: 'white',
    },
  },
};

export default function CustomCheckbox({ id, children, checked, onChange }: ICustomCheckboxProps) {
  const [isChecked, setIsChecked] = useState(checked);

  const handleChange = () => {
    const newChecked = !isChecked;
    setIsChecked(newChecked);
    onChange(newChecked);
  };

  return (
    <Box sx={{ ...styles.mainBox }}>
      <input type="checkbox" checked={checked} onChange={handleChange} id={id} />
      <div />
      <label htmlFor={id}>{children}</label>
    </Box>
  );
}
