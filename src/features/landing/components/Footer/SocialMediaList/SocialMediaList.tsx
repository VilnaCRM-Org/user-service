import { Stack } from '@mui/material';
import React from 'react';

import { ISocialMedia } from '../../../types/social-media';
import { SocialMediaItem } from '../SocialMediaItem';

import { socialMediaListStyles } from './styles';

function SocialMediaList({ socialLinks }: { socialLinks: ISocialMedia[] }) {
  return (
    <Stack
      direction="row"
      alignItems="center"
      sx={socialMediaListStyles.listWrapper}
    >
      {socialLinks.map(item => (
        <SocialMediaItem item={item} key={item.id} />
      ))}
    </Stack>
  );
}

export default SocialMediaList;
