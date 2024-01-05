import { Stack } from '@mui/material';
import React from 'react';

import { SOCIAL_LINKS } from '../../../utils/constants/constants';
import { SocialItem } from '../SocialItem';

function SocialList() {
  return (
    <Stack flexWrap="wrap" direction="row" gap="12px">
      {SOCIAL_LINKS.map(item => (
        <SocialItem item={item} key={item.id} />
      ))}
    </Stack>
  );
}

export default SocialList;
