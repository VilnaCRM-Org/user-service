import { Grid } from '@mui/material';
import React from 'react';

import { ISocialLink } from '../../../types/authentication/social';
import { SocialItem } from '../SocialItem';

import { socialListStyles } from './styles';

function SocialList({ socialLinks }: { socialLinks: ISocialLink[] }) {
  return (
    <Grid sx={socialListStyles.listWrapper}>
      {socialLinks.map(item => (
        <SocialItem item={item} key={item.id} />
      ))}
    </Grid>
  );
}

export default SocialList;
