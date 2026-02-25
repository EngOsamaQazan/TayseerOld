import { Controller, Get, Post, Put, Delete, Body, Param, Query, UseGuards, Request, ParseIntPipe } from '@nestjs/common';
import { ApiBearerAuth, ApiTags, ApiOperation } from '@nestjs/swagger';
import { CategoriesService } from './categories.service';
import { CreateCategoryDto } from './dto/create-category.dto';
import { JwtAuthGuard } from '../auth/guards/jwt-auth.guard';

@ApiTags('Categories - الفئات المالية')
@ApiBearerAuth()
@UseGuards(JwtAuthGuard)
@Controller('categories')
export class CategoriesController {
  constructor(private readonly categoriesService: CategoriesService) {}

  @Post()
  @ApiOperation({ summary: 'إضافة فئة (دخل/مصاريف)' })
  create(@Body() dto: CreateCategoryDto, @Request() req: any) {
    return this.categoriesService.create(dto, req.user.tenantId);
  }

  @Post('seed')
  @ApiOperation({ summary: 'تعبئة الفئات الافتراضية' })
  seed(@Request() req: any) {
    return this.categoriesService.seed(req.user.tenantId);
  }

  @Get(':type')
  @ApiOperation({ summary: 'قائمة الفئات حسب النوع (income/expense)' })
  findByType(@Param('type') type: string, @Request() req: any) {
    return this.categoriesService.findByType(type, req.user.tenantId);
  }

  @Put(':id')
  @ApiOperation({ summary: 'تعديل فئة' })
  update(@Param('id', ParseIntPipe) id: number, @Body() dto: Partial<CreateCategoryDto>, @Request() req: any) {
    return this.categoriesService.update(id, dto, req.user.tenantId);
  }

  @Delete(':id')
  @ApiOperation({ summary: 'تعطيل فئة' })
  remove(@Param('id', ParseIntPipe) id: number, @Request() req: any) {
    return this.categoriesService.remove(id, req.user.tenantId);
  }
}
