import * as React from 'react';
import { Grid, IconButton } from '@mui/material';
import { VilnaMainIcon } from '@/features/landing/components/Icons/VilnaMainIcon/VilnaMainIcon';
import { CustomLink } from '@/components/ui/CustomLink/CustomLink';
import { VilnaMenuIcon } from '@/features/landing/components/Icons/VilnaMenuIcon/VilnaMenuIcon';
import {
  HeaderDrawerActionButtons,
} from '@/features/landing/components/Header/HeaderDrawerActionButtons/HeaderDrawerActionButtons';

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

export function HeaderTopContentInMobileView({
                                               onSignInButtonClick,
                                               onTryItOutButtonClick,
                                               onMobileViewDrawerClose,
                                               onDrawerClose
                                             }: IHeaderTopContentInMobileViewProps) {
  return (
    <>
      <Grid container
            sx={{
              display: 'flex',
              justifyContent: 'space-between',
              alignItems: 'center',
              marginBottom: '12px',
            }}>
        <CustomLink href={'/'}
                    style={logoStyle}>
          <VilnaMainIcon />
        </CustomLink>
        <IconButton
          onClick={onMobileViewDrawerClose}
          edge='start' color='inherit' aria-label='menu'
          sx={{
            justifySelf: 'flex-end',
          }}>
          <VilnaMenuIcon isActive={true} />
        </IconButton>
      </Grid>
      <HeaderDrawerActionButtons onSignInButtonClick={onSignInButtonClick}
                                 onTryItOutButtonClick={onTryItOutButtonClick} onDrawerClose={onDrawerClose}/>
    </>
  );
}
