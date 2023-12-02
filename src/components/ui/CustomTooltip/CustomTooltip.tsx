import { Grid, Tooltip, Typography } from '@mui/material';
import Image from 'next/image';
import React, { useMemo } from 'react';

interface ICustomTooltipProps {
  title: string;
  text: string;
  icons: string[];
  children: React.ReactNode;
}

const styles = {
  tooltip: {
    backgroundColor: '#FFF',
    color: '#000',
    maxWidth: 'none',
  },
  content: {
    width: '100%',
    maxWidth: '330px',
    height: '213px',
    display: 'flex',
    flexDirection: 'column',
    alignItems: 'flex-start',
    padding: '18px 24px 18px 24px',
    borderRadius: '8px',
    border: '1px solid #D0D4D8',
    backgroundColor: '#FFF',
  },
};

export default function CustomTooltip({ title, text, icons, children }: ICustomTooltipProps) {
  const iconsJSX = useMemo(
    () =>
      icons.map((iconSrc, index) => (
        <Grid
          item
          key={iconSrc}
          sx={{
            maxWidth: '25%',
            height: '100%',
            maxHeight: '45px',
            alignSelf: 'center',
          }}
          xs={3}
        >
          <Image
            src={iconSrc}
            alt={`Integration ${index}`}
            width={91}
            height={91}
            style={{
              width: '100%',
              height: '100%',
              objectFit: 'contain',
              pointerEvents: 'none',
              userSelect: 'none',
            }}
          />
        </Grid>
      )),
    [icons]
  );

  return (
    <Tooltip
      sx={{ ...styles.tooltip }}
      arrow
      title={
        <div style={{ ...styles.content, flexDirection: 'column' }}>
          <Typography
            variant="h5"
            component="h5"
            style={{
              color: '#000',
              fontFamily: 'GolosText-Regular, sans-serif',
              fontSize: '18px',
              fontStyle: 'normal',
              fontWeight: 600,
              lineHeight: 'normal',
            }}
          >
            {title}
          </Typography>
          <Typography
            variant="body1"
            component="p"
            style={{
              color: '#000',
              fontFamily: 'Inter-Regular, sans-serif',
              fontSize: '14px',
              fontStyle: 'normal',
              fontWeight: 500,
              lineHeight: '18px',
              marginBottom: '22px',
            }}
          >
            {text}
          </Typography>
          <Grid container spacing={1}>
            {[...iconsJSX]}
          </Grid>
        </div>
      }
    >
      <span>{children}</span>
    </Tooltip>
  );
}
