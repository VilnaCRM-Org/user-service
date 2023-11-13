import React, { useMemo } from 'react';
import { createStyles, Grid, Tooltip, Typography } from '@mui/material';
import { makeStyles, Theme } from '@material-ui/core/styles';

interface IWhyWeTooltipProps {
  title: string;
  text: string;
  icons: string[];
  children: React.ReactNode;
}

const useCustomStyles = makeStyles((theme: Theme) => createStyles({
  tooltip: {
    backgroundColor: theme.palette.common.white || '#FFF',
    color: theme.palette.text.primary || '#000',
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
}));


export function WhyWeTooltip({ title, text, icons, children }: IWhyWeTooltipProps) {
  const classes = useCustomStyles();

  const iconsJSX = useMemo(() => {
    return icons.map((iconSrc, index) => (
      <Grid item key={index} sx={{
        maxWidth: '25%',
        height: '100%',
        maxHeight: '45px',
        alignSelf: 'center',
      }} xs={3}>
        <img src={iconSrc} alt={`Integration image ${index}`}
             style={{ width: '100%', height: '100%', objectFit: 'contain' }} />
      </Grid>
    ));
  }, [icons]);

  return (
    <Tooltip
      arrow
      title={
        <div className={classes.content}>
          <Typography
            variant={'h5'}
            component={'h5'}
            sx={{
              color: '#000',
              fontFamily: 'GolosText-Regular, sans-serif',
              fontSize: '18px',
              fontStyle: 'normal',
              fontWeight: 600,
              lineHeight: 'normal',
            }}>{title}</Typography>
          <Typography
            variant='body1'
            component={'p'}
            sx={{
              color: '#000',
              fontFamily: 'Inter-Regular, sans-serif',
              fontSize: '14px',
              fontStyle: 'normal',
              fontWeight: 500,
              lineHeight: '18px',
              marginBottom: '22px',
            }}>{text}</Typography>
          <Grid container spacing={1}>
            {[...iconsJSX]}
          </Grid>
        </div>
      }
      sx={{}}
      classes={{ tooltip: classes.tooltip }}
    >
      <span>{children}</span>
    </Tooltip>
  );
}
