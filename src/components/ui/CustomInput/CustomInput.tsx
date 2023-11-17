import React, { useState } from 'react';
import TextField from '@mui/material/TextField';
import { makeStyles, Theme } from '@material-ui/core/styles';
import { Typography } from '@material-ui/core';

const useStyles = makeStyles((theme: Theme) => ({
  root: {
    '& .MuiInput-root': {
      transition: theme.transitions.create(['border-color', 'box-shadow']),
      '&:hover': {
        borderColor: theme.palette.primary.main,
      },
      '&.Mui-focused': {
        borderColor: theme.palette.primary.main,
      },
      '&.Mui-disabled': {
        opacity: 0.6,
      },
    },
  },
  errorMessage: {
    color: theme.palette.error.main,
    marginTop: theme.spacing(1),
  },
}));

// CustomInput component
interface ICustomInputProps {
  label: string;
  value: string;
  onChange: (value: string) => void;
  disabled?: boolean;
  error?: string;
}

export function CustomInput({
                              label,
                              value,
                              onChange,
                              disabled = false,
                              error = '',
                            }: ICustomInputProps) {
  const classes = useStyles();
  const [isFocused, setIsFocused] = useState(false);

  const handleFocus = () => {
    setIsFocused(true);
  };

  const handleBlur = () => {
    setIsFocused(false);
  };

  return (
    <div className={classes.root}>
      <TextField
        label={label}
        value={value}
        onChange={(e) => onChange(e.target.value)}
        onFocus={handleFocus}
        onBlur={handleBlur}
        disabled={disabled}
        variant='outlined'
        fullWidth
      />
      {error && isFocused && (
        <Typography className={classes.errorMessage}>{error}</Typography>
      )}
    </div>
  );
}

