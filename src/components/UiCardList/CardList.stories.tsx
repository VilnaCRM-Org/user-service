import type { Meta, StoryObj } from '@storybook/react';

import WhyUsCodeIcon from '../../features/landing/assets/svg/why-us/code.svg';
import WhyUsIntegrationsIcon from '../../features/landing/assets/svg/why-us/integrations.svg';
import WhyUsMigrationIcon from '../../features/landing/assets/svg/why-us/migration.svg';
import WhyUsServicesIcon from '../../features/landing/assets/svg/why-us/services.svg';
import WhyUsSettingsIcon from '../../features/landing/assets/svg/why-us/settings.svg';
import WhyUsTemplatesIcon from '../../features/landing/assets/svg/why-us/templates.svg';

import UiCardList from './index';

const meta: Meta<typeof UiCardList> = {
  title: 'UiComponents/UiCardList',
  component: UiCardList,
  tags: ['autodocs'],
  argTypes: {
    cardList: {
      control: 'object',
      description: 'List of card items',
    },
  },
};

export default meta;

type Story = StoryObj<typeof UiCardList>;

export const CardListLarge: Story = {
  args: {
    cardList: [
      {
        type: 'largeCard',
        id: 'card-item-1',
        imageSrc: WhyUsCodeIcon,
        title: 'Open source',
        text: 'Для випадків, коли ви не знайшли потрібну готову інтеграцію',
        alt: 'why_us.alt_image.alt_open_source',
      },
      {
        type: 'largeCard',
        id: 'card-item-2',
        imageSrc: WhyUsSettingsIcon,
        title: 'Легкість у налаштуванні',
        text: 'Для випадків, коли ви не знайшли потрібну готову інтеграцію',
        alt: 'why_us.alt_image.alt_ease_of_setup',
      },
      {
        type: 'largeCard',
        id: 'card-item-3',
        imageSrc: WhyUsTemplatesIcon,
        title: 'Готові шаблони',
        text: 'У вас: онлайн-магазин, курси чи веб-студія. У нас: спеціальні шаблони, які збережуть ваш час',
        alt: 'why_us.alt_image.alt_ready_templates',
      },
      {
        type: 'largeCard',
        id: 'card-item-4',
        imageSrc: WhyUsServicesIcon,
        title: 'Ідеальна для сервісів',
        text: 'Для випадків, коли ви не знайшли потрібну готову інтеграцію',
        alt: 'why_us.alt_image.alt_ideal_for_services',
      },
      {
        type: 'largeCard',
        id: 'card-item-5',
        imageSrc: WhyUsIntegrationsIcon,
        title: 'Усі потрібні інтеграції',
        text: 'Для випадків, коли ви не знайшли потрібну готову інтеграцію',
        alt: 'why_us.alt_image.alt_all_required_integrations',
      },
      {
        type: 'largeCard',
        id: 'card-item-6',
        imageSrc: WhyUsMigrationIcon,
        title: 'Бонус: легка міграція',
        text: 'Для випадків, коли ви не знайшли потрібну готову інтеграцію',
        alt: 'why_us.alt_image.alt_bonus',
      },
    ],
  },
};

export const CardListSmall: Story = {
  args: {
    cardList: [
      {
        type: 'smallCard',
        id: 'card-item-1',
        imageSrc: WhyUsCodeIcon,
        title: 'Open source',
        text: 'Для випадків, коли ви не знайшли потрібну готову інтеграцію',
        alt: 'why_us.alt_image.alt_open_source',
      },
      {
        type: 'smallCard',
        id: 'card-item-2',
        imageSrc: WhyUsSettingsIcon,
        title: 'Легкість у налаштуванні',
        text: 'Для випадків, коли ви не знайшли потрібну готову інтеграцію',
        alt: 'why_us.alt_image.alt_ease_of_setup',
      },
      {
        type: 'smallCard',
        id: 'card-item-3',
        imageSrc: WhyUsTemplatesIcon,
        title: 'Готові шаблони',
        text: 'Для випадків, коли ви не знайшли потрібну готову інтеграцію',
        alt: 'why_us.alt_image.alt_ready_templates',
      },
      {
        type: 'smallCard',
        id: 'card-item-4',
        imageSrc: WhyUsServicesIcon,
        title: 'Ідеальна для сервісів',
        text: 'Для випадків, коли ви не знайшли потрібну готову інтеграцію',
        alt: 'why_us.alt_image.alt_ideal_for_services',
      },
    ],
  },
};
