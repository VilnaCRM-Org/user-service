/* eslint-disable react/jsx-no-bind */
import { ListItem, Stack, List, Button } from '@mui/material';
import React from 'react';
import { useTranslation } from 'react-i18next';

import { scrollTo } from '../../../utils/helpers/scrollTo';

import { navLinkStyles } from './styles';

function NavLink({ links }: { links: { id: string; value: string }[] }) {
  const { t } = useTranslation();

  const handleOnClickScroll = (id: string, offset: number) => {
    scrollTo(id, offset);
  };

  return (
    <Stack component="nav" ml="106px">
      <List sx={{ display: 'flex' }}>
        {links.map(link => (
          <ListItem key={link.id}>
            <Button
              component="a"
              sx={navLinkStyles.navLink}
              onClick={() => handleOnClickScroll(link.id, 100)}
            >
              {t(link.value)}
            </Button>
          </ListItem>
        ))}
      </List>
    </Stack>
  );
}

export default NavLink;
