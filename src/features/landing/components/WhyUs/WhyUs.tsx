import { Box } from '@mui/material';
import React from 'react';

import UiCardList from '@/components/UiCard/UiCardList';

import WhyUsCodeIcon from '../../assets/img/why-we-section/code.png';
import WhyUsIntegrationsIcon from '../../assets/img/why-we-section/integrations.png';
import WhyUsMigrationIcon from '../../assets/img/why-we-section/migration.png';
import WhyUsServicesIcon from '../../assets/img/why-we-section/services.png';
import WhyUsSettingsIcon from '../../assets/img/why-we-section/settings.png';
import WhyUsTemplatesIcon from '../../assets/img/why-we-section/templates.png';

import { Heading } from './Heading';
import { whyUsStyles } from './styles';

function WhyUs() {
  const cardList = [
    {
      id: 'card-item-1',
      imageSrc: WhyUsCodeIcon,
      title: 'why_we.headers.header_open_source',
      text: 'why_we.texts.text_open_source',
      alt: 'why_we.alt_image.alt_open_source',
    },
    {
      id: 'card-item-2',
      imageSrc: WhyUsSettingsIcon,
      title: 'why_we.headers.header_ease_of_setup',
      text: 'why_we.texts.text_configure_system',
      alt: 'why_we.alt_image.alt_ease_of_setup',
    },
    {
      id: 'card-item-3',
      imageSrc: WhyUsTemplatesIcon,
      title: 'why_we.headers.header_ready_templates',
      text: 'why_we.texts.text_you_have_store',
      alt: 'why_we.alt_image.alt_ready_templates',
    },
    {
      id: 'card-item-4',
      imageSrc: WhyUsServicesIcon,
      title: 'why_we.headers.header_ideal_for_services',
      text: 'why_we.texts.text_we_know_specific_needs',
      alt: 'why_we.alt_image.alt_ideal_for_services',
    },
    {
      id: 'card-item-5',
      imageSrc: WhyUsIntegrationsIcon,
      title: 'why_we.headers.header_all_required_integrations',
      text: 'why_we.texts.text_connect_your_cms',
      alt: 'why_we.alt_image.alt_all_required_integrations',
    },
    {
      id: 'card-item-6',
      imageSrc: WhyUsMigrationIcon,
      title: 'why_we.headers.header_bonus',
      text: 'why_we.texts.text_switch_to_vilna',
      alt: 'why_we.alt_image.alt_bonus',
    },
  ];
  return (
    <Box sx={whyUsStyles.wrapper} id="Advantages" component="section">
      <Heading />
      <UiCardList cardList={cardList} type="large" />
    </Box>
  );
}

export default WhyUs;
