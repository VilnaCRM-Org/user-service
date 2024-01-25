import { Grid } from '@mui/material';
import React from 'react';

import { ISocialLink } from '../../../types/authentication/social';
import { SocialItem } from '../SocialItem';

import styles from './styles';

function SocialList({ socialLinks }: { socialLinks: ISocialLink[] }) {
  return (
    <Grid sx={styles.listWrapper}>
      {socialLinks.map(item => (
        <SocialItem item={item} key={item.id} />
      ))}
    </Grid>
  );
}

export default SocialList;
