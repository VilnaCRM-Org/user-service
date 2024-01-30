import { Stack } from '@mui/material';
import React from 'react';

import { ISocialMedia } from '../../../types/social-media';
import { SocialMediaItem } from '../SocialMediaItem';

import styles from './styles';

function SocialMediaList({
  socialLinks,
}: {
  socialLinks: ISocialMedia[];
}): React.ReactElement {
  return (
    <Stack direction="row" alignItems="center" sx={styles.listWrapper}>
      {socialLinks.map(item => (
        <SocialMediaItem item={item} key={item.id} />
      ))}
    </Stack>
  );
}

export default SocialMediaList;
