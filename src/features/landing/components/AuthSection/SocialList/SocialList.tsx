import { Grid } from '@mui/material';
import React from 'react';

import { SOCIAL_LINKS } from '../../../utils/constants/constants';
import { SocialItem } from '../SocialItem';

import { socialListStyles } from './styles';

function SocialList() {
  return (
    <Grid sx={socialListStyles.listWrapper}>
      {SOCIAL_LINKS.map(item => (
        <SocialItem item={item} key={item.id} />
      ))}
    </Grid>
  );
}

export default SocialList;
