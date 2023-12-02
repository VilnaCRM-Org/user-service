import { Grid, IconButton } from '@mui/material';
import * as React from 'react';

import CustomLink from '@/components/ui/CustomLink/CustomLink';

import VilnaMainIcon from '../../Icons/VilnaMainIcon/VilnaMainIcon';
import VilnaMenuIcon from '../../Icons/VilnaMenuIcon/VilnaMenuIcon';
import HeaderDrawerActionButtons from '../HeaderDrawerActionButtons/HeaderDrawerActionButtons';

interface IHeaderTopContentInMobileViewProps {
  onSignInButtonClick: () => void;
  onTryItOutButtonClick: () => void;
  onMobileViewDrawerClose: () => void;
  onDrawerClose: () => void;
}

const logoStyle = {
  width: '130px',
  justifySelf: 'flex-start',
  textDecoration: 'none',
  color: 'black',
};

export default function HeaderTopContentInMobileView({
  onSignInButtonClick,
  onTryItOutButtonClick,
  onMobileViewDrawerClose,
  onDrawerClose,
}: IHeaderTopContentInMobileViewProps) {
  return (
    <>
      <Grid
        container
        sx={{
          display: 'flex',
          justifyContent: 'space-between',
          alignItems: 'center',
          marginBottom: '12px',
        }}
      >
        <CustomLink href="/" style={logoStyle}>
          <VilnaMainIcon />
        </CustomLink>
        <IconButton
          onClick={onMobileViewDrawerClose}
          edge="start"
          color="inherit"
          aria-label="menu"
          sx={{
            justifySelf: 'flex-end',
          }}
        >
          <VilnaMenuIcon isActive />
        </IconButton>
      </Grid>
      <HeaderDrawerActionButtons
        onSignInButtonClick={onSignInButtonClick}
        onTryItOutButtonClick={onTryItOutButtonClick}
        onDrawerClose={onDrawerClose}
      />
    </>
  );
}
