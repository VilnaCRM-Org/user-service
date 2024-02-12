import { Grid } from '@mui/material';
import React from 'react';

import { SocialLink } from '../../../types/authentication/social';
import { SocialItem } from '../SocialItem';

import styles from './styles';

function SocialList({
  socialLinks,
}: {
  socialLinks: SocialLink[];
}): React.ReactElement {
  return (
    <Grid sx={styles.listWrapper}>
      {socialLinks.map(item => (
        <SocialItem item={item} key={item.id} />
      ))}
    </Grid>
  );
}

export default SocialList;
